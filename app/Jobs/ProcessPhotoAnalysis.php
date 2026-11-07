<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\AnalyzeFoodPhotoAction;
use App\Actions\CreateAnalysisDraftAction;
use App\Actions\FailPhotoAnalysis;
use App\Actions\StoreMealPhotoAction;
use App\Enums\AnalysisDraftSource;
use App\Enums\PhotoAnalysisStatus;
use App\Exceptions\Billing\PhotoLimitExceeded;
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

    public function handle(AnalyzeFoodPhotoAction $analyze, CreateAnalysisDraftAction $drafts, StoreMealPhotoAction $photos, FailPhotoAnalysis $failures): void
    {
        $request = PhotoAnalysisRequest::query()->metadata()->find($this->analysisRequestId);

        if ($request === null) {
            return;
        }

        $failureCode = $request->staleFailureCode();
        if ($failureCode !== null) {
            $failures->handle($request, $failureCode, onlyIfStale: true);

            return;
        }

        $claimed = PhotoAnalysisRequest::query()->whereKey($request->id)
            ->where('status', PhotoAnalysisStatus::Queued)->whereFuture('deadline_at')->where('expires_at', '>', now()->addSeconds(PhotoAnalysisRequest::PROCESSING_BUDGET_SECONDS))
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
                $failures->handle($request, 'invalid_photo');

                return;
            }

            $result = $analyze->handle($base64, $request->mime_type, context: $context, analysisRequestId: $request->id);

            if ($result->items->count() === 0) {
                $failures->handle($request, 'no_food');

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

            if ($token !== null && $context->user !== null) {
                try {
                    $photos->handle($context->user, $image->bytes, $token);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        } catch (Throwable $throwable) {
            $code = match (true) {
                ! $request->hasProcessingBudget() => 'expired',
                $throwable instanceof PhotoLimitExceeded => 'limit_reached',
                default => 'provider_error',
            };
            $failures->handle($request, $code);

            throw $throwable;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $request = PhotoAnalysisRequest::query()->metadata()->find($this->analysisRequestId);
        if ($request !== null) {
            resolve(FailPhotoAnalysis::class)->handle($request, $exception instanceof TimeoutExceededException ? 'timeout' : 'interrupted');
        }
    }
}
