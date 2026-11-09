<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\AnalyzeFoodPhotoAction;
use App\Actions\CreateAnalysisDraftAction;
use App\Actions\FailPhotoAnalysis;
use App\Actions\KeepScannedPhoto;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoModel;
use App\Data\FoodAnalysisData;
use App\Enums\AnalysisDraftSource;
use App\Enums\PhotoAnalysisFailure;
use App\Enums\PhotoAnalysisStatus;
use App\Exceptions\Billing\PhotoLimitExceeded;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Exceptions\PhotoAnalysisNotProcessingException;
use App\Models\PhotoAnalysisRequest;
use App\Utilities\Base64Image;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Timeout(75)]
#[Tries(1)]
#[FailOnTimeout]
final class ProcessPhotoAnalysis implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(public int $analysisRequestId) {}

    public function handle(ManagesPhotoAnalyses $photoAnalyses, AnalyzeFoodPhotoAction $analyzer, CreateAnalysisDraftAction $drafts, KeepScannedPhoto $keepPhoto, FailPhotoAnalysis $failures): void
    {
        $request = PhotoAnalysisRequest::query()->metadata()->find($this->analysisRequestId);

        if ($request === null) {
            return;
        }

        $staleFailure = $request->staleFailureCode();
        if ($staleFailure instanceof PhotoAnalysisFailure) {
            $failures->handle($request, $staleFailure, onlyIfStale: true);

            return;
        }

        $claimed = PhotoAnalysisRequest::query()->whereKey($request->id)
            ->where('status', PhotoAnalysisStatus::Queued)->admissible()
            ->update(['status' => PhotoAnalysisStatus::Processing, 'started_at' => now(), 'deadline_at' => now()->addSeconds(PhotoAnalysisRequest::PROCESSING_BUDGET_SECONDS)]);

        if ($claimed === 0) {
            return;
        }

        $request->refresh();
        $context = $request->context();
        Log::info('photo_analysis_started', [
            'request_id' => $request->request_id,
            'source' => $request->source,
            'queue_wait_ms' => max(0, (int) $request->created_at->diffInMilliseconds(now())),
        ]);

        try {
            $base64 = $request->image_base64;
            $image = Base64Image::decode($base64 ?? '');

            if ($base64 === null || ! $image instanceof Base64Image) {
                $failures->handle($request, PhotoAnalysisFailure::InvalidPhoto);

                return;
            }

            $result = $photoAnalyses->analyze($context, function (PhotoModel $model) use ($analyzer, $request, $base64, $context): FoodAnalysisData {
                throw_unless($request->isStillProcessing(), PhotoAnalysisNotProcessingException::class);

                return $analyzer->analyzeUsingModel($base64, $request->mime_type, $model, user: $context->user, requestId: $request->request_id);
            });

            if ($result->items->count() === 0) {
                $failures->handle($request, PhotoAnalysisFailure::NoFood);

                return;
            }

            $token = DB::transaction(function () use ($request, $drafts, $result, $context): ?string {
                $current = PhotoAnalysisRequest::query()->metadata()->lockForUpdate()->find($request->id);

                if ($current === null || $current->status !== PhotoAnalysisStatus::Processing || $current->deadline_at->isPast() || $current->isExpired()) {
                    return null;
                }

                $token = $drafts->handle($result, AnalysisDraftSource::PublicSnapToTrack, $context->user?->id);
                $current->update(['status' => PhotoAnalysisStatus::Succeeded, 'draft_token' => $token, 'image_base64' => null, 'completed_at' => now()]);
                $current->recordOutcome();

                return $token;
            });

            if ($token !== null) {
                $keepPhoto->handle($request, $context->user, $image->bytes, $token);
            }
        } catch (PhotoAnalysisNotProcessingException) {
            $failures->handle($request, $request->hasProcessingBudget() ? PhotoAnalysisFailure::Timeout : PhotoAnalysisFailure::Expired);
        } catch (PhotoLimitExceeded|UsageLimitExceededException) {
            $failures->handle($request, $request->hasProcessingBudget() ? PhotoAnalysisFailure::LimitReached : PhotoAnalysisFailure::Expired);
        } catch (Throwable $throwable) {
            $failures->handle($request, $request->hasProcessingBudget() ? PhotoAnalysisFailure::ProviderError : PhotoAnalysisFailure::Expired);

            throw $throwable;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $request = PhotoAnalysisRequest::query()->metadata()->find($this->analysisRequestId);
        if ($request !== null) {
            resolve(FailPhotoAnalysis::class)->handle($request, $exception instanceof TimeoutExceededException ? PhotoAnalysisFailure::Timeout : PhotoAnalysisFailure::Interrupted);
        }
    }
}
