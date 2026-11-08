<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EstimateValidationStatus;
use App\Models\AnalysisDraft;
use App\Models\MealEstimate;
use Illuminate\Support\Arr;

final readonly class RecordMealEstimate
{
    public function __construct(private RecordMealCorrection $recordMealCorrection) {}

    public function handle(AnalysisDraft $draft, int $userId, string $groupId): void
    {
        $modelVersion = $draft->payload['analyzer_version'] ?? null;

        MealEstimate::query()->create([
            'user_id' => $userId,
            'health_group_id' => $groupId,
            'source' => $draft->source,
            'model_version' => is_string($modelVersion) ? $modelVersion : null,
            'original_prediction' => Arr::except($draft->payload, ['analyzer_version']),
            'validation_status' => EstimateValidationStatus::AiGenerated,
        ]);

        $this->recordMealCorrection->handle($userId, $groupId);
    }
}
