<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\Billing\ResolvesFailedPhotoAnalysis;
use App\Data\FoodAnalysisData;
use App\Enums\AnalysisDraftSource;
use App\Enums\PhotoAnalysisStatus;
use App\Models\PhotoAnalysisRequest;
use Illuminate\Support\Facades\DB;

final readonly class FailPhotoAnalysis
{
    public function __construct(private ResolvesFailedPhotoAnalysis $resolver, private CreateAnalysisDraftAction $drafts) {}

    public function handle(PhotoAnalysisRequest $request, string $code, bool $onlyIfStale = false): bool
    {
        $updated = DB::transaction(function () use ($request, $code, $onlyIfStale): bool {
            $current = PhotoAnalysisRequest::query()->metadata()->lockForUpdate()->find($request->id);

            if ($current === null || ! $current->isPending()) {
                return false;
            }

            if ($onlyIfStale && $current->staleFailureCode() === null) {
                return false;
            }

            $context = $current->context();
            $result = $this->resolver->handle($context);

            if ($result instanceof FoodAnalysisData && $result->items->count() > 0 && ! $current->isExpired()) {
                $token = $this->drafts->handle($result, AnalysisDraftSource::PublicSnapToTrack, $context->user?->id);
                $current->update(['status' => PhotoAnalysisStatus::Succeeded, 'draft_token' => $token, 'image_base64' => null, 'completed_at' => now()]);

                return true;
            }

            $current->update([
                'status' => PhotoAnalysisStatus::Failed,
                'failure_code' => $code,
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
}
