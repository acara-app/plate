<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\Billing\ResolvesFailedPhotoAnalysis;
use App\Data\FoodAnalysisData;
use App\Enums\AnalysisDraftSource;
use App\Enums\PhotoAnalysisFailure;
use App\Enums\PhotoAnalysisStatus;
use App\Models\PhotoAnalysisRequest;
use App\Models\User;
use App\Utilities\Base64Image;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class FailPhotoAnalysis
{
    public function __construct(
        private ResolvesFailedPhotoAnalysis $resolver,
        private CreateAnalysisDraftAction $drafts,
        private StoreMealPhotoAction $photos,
    ) {}

    public function handle(PhotoAnalysisRequest $request, PhotoAnalysisFailure $failure, bool $onlyIfStale = false): bool
    {
        $updated = DB::transaction(function () use ($request, $failure, $onlyIfStale): bool {
            $current = PhotoAnalysisRequest::query()->metadata()->lockForUpdate()->find($request->id);

            if ($current === null || ! $current->isPending()) {
                return false;
            }

            if ($onlyIfStale && ! $current->staleFailureCode() instanceof PhotoAnalysisFailure) {
                return false;
            }

            $context = $current->context();
            $result = $this->resolver->handle($context);

            if ($result instanceof FoodAnalysisData && $result->items->count() > 0 && ! $current->isExpired()) {
                $token = $this->drafts->handle($result, AnalysisDraftSource::PublicSnapToTrack, $context->user?->id);
                $user = $context->user;

                if ($user instanceof User) {
                    $image = PhotoAnalysisRequest::query()->select(['id', 'image_base64'])->find($current->id)?->image_base64;
                    DB::afterCommit(fn () => $this->keepMealPhoto($user, $image, $token));
                }

                $current->update(['status' => PhotoAnalysisStatus::Succeeded, 'draft_token' => $token, 'image_base64' => null, 'completed_at' => now()]);

                return true;
            }

            $current->update([
                'status' => PhotoAnalysisStatus::Failed,
                'failure_code' => $failure,
                'failed_at' => now(),
                ...$current->isExpired() ? ['image_base64' => null, 'draft_token' => null] : [],
            ]);

            return true;
        });

        if (! $updated) {
            return false;
        }

        $request->refresh();
        $request->recordOutcome();

        return true;
    }

    private function keepMealPhoto(User $user, ?string $base64, string $token): void
    {
        $image = Base64Image::decode($base64 ?? '');

        if (! $image instanceof Base64Image) {
            return;
        }

        try {
            $this->photos->handle($user, $image->bytes, $token);
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }
}
