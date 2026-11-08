<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\ReviewedMealData;
use App\Enums\EstimateValidationStatus;
use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\MealEstimate;
use Illuminate\Database\Eloquent\Collection;

final readonly class RecordMealCorrection
{
    public function handle(int $userId, string $groupId): void
    {
        $estimate = MealEstimate::query()
            ->where('user_id', $userId)
            ->where('health_group_id', $groupId)
            ->first();

        if (! $estimate instanceof MealEstimate) {
            return;
        }

        $saved = $this->savedMeal(HealthSyncSample::query()
            ->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->get());

        $isCorrected = $this->comparable($saved) !== $this->comparable($estimate->predictedMeal());
        $correctionChanged = $estimate->corrected_value === null || $this->comparable($estimate->corrected_value) !== $this->comparable($saved);

        $estimate->update([
            'validation_status' => $isCorrected ? EstimateValidationStatus::UserCorrected : EstimateValidationStatus::AiGenerated,
            'corrected_value' => $isCorrected ? $saved : null,
            'corrected_at' => match (true) {
                ! $isCorrected => null,
                $correctionChanged => now(),
                default => $estimate->corrected_at,
            },
        ]);
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return array{items: array<array-key, mixed>|null, totals: array<string, float|null>}
     */
    private function savedMeal(Collection $samples): array
    {
        $analysis = HealthSyncSample::firstMetadataIn($samples, 'snap_to_track');
        $total = fn (HealthSyncType $type): ?float => $samples->firstWhere('type_identifier', $type->value)?->value;

        return [
            'items' => is_array($analysis) && is_array($analysis['items'] ?? null) ? $analysis['items'] : null,
            'totals' => [
                'calories' => $total(HealthSyncType::DietaryEnergy),
                'protein' => $total(HealthSyncType::Protein),
                'carbs' => $total(HealthSyncType::Carbohydrates),
                'fat' => $total(HealthSyncType::TotalFat),
                'fiber' => $total(HealthSyncType::Fiber),
            ],
        ];
    }

    /**
     * @param  array<array-key, mixed>  $meal
     * @return array{items: list<array<string, float|string|null>>|null, totals: array<array-key, float|null>}
     */
    private function comparable(array $meal): array
    {
        return [
            'items' => is_array($meal['items'] ?? null) ? array_map(fn (array $item): array => [
                'name' => mb_strtolower(mb_trim(is_string($item['name'] ?? null) ? $item['name'] : '')),
                'grams' => $this->amount($item['grams'] ?? null),
                'calories' => $this->amount($item['calories'] ?? null),
                'protein' => $this->amount($item['protein'] ?? null),
                'carbs' => $this->amount($item['carbs'] ?? null),
                'fat' => $this->amount($item['fat'] ?? null),
                'fiber' => $this->amount($item['fiber'] ?? null),
            ], ReviewedMealData::normalizeItems($meal['items'])) : null,
            'totals' => array_map($this->amount(...), is_array($meal['totals'] ?? null) ? $meal['totals'] : []),
        ];
    }

    private function amount(mixed $value): ?float
    {
        return is_numeric($value) ? round((float) $value, 1) : null;
    }
}
