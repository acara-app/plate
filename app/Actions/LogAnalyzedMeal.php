<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\FoodAnalysisData;
use App\Data\ReviewedMealData;
use App\Enums\AnalysisDraftSource;
use App\Enums\SnapFunnelStage;
use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Services\Billing\SnapFunnel;

final readonly class LogAnalyzedMeal
{
    public function __construct(
        private LogReviewedMealAction $logReviewedMeal,
        private SnapFunnel $funnel,
    ) {}

    public function handle(AnalysisDraft $draft, User $user): ?HealthSyncSample
    {
        $meal = new ReviewedMealData(
            items: ReviewedMealData::normalizeItems(FoodAnalysisData::from($draft->payload)->items->toArray()),
            measuredAt: $draft->created_at->toImmutable(),
        );

        $groupId = $this->logReviewedMeal->handle($draft, $meal->toHealthLogData($draft), $user);

        if ($draft->source === AnalysisDraftSource::PublicSnapToTrack) {
            $this->funnel->record(SnapFunnelStage::MealSaved, (string) $draft->id, ['items' => count($meal->items)]);
        }

        return HealthSyncSample::query()
            ->where('user_id', $user->id)
            ->where('group_id', $groupId)
            ->orderBy('id')
            ->first();
    }
}
