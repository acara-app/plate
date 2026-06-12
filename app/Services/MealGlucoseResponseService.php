<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MealGlucosePatternData;
use App\Data\MealGlucoseResponseData;
use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final readonly class MealGlucoseResponseService
{
    public const int BASELINE_WINDOW_MINUTES = 90;

    public const int RESPONSE_WINDOW_START_MINUTES = 30;

    public const int RESPONSE_WINDOW_END_MINUTES = 180;

    public const float CARB_BAND_RATIO = 0.25;

    public const int PATTERN_MIN_MEALS = 3;

    public const int PATTERN_CANDIDATE_LIMIT = 20;

    public const int PATTERN_LOOKBACK_DAYS = 90;

    private const array MEAL_TYPES = [
        'carbohydrates',
        'protein',
        'totalFat',
        'dietaryEnergy',
    ];

    public function forMeal(User $user, CarbonInterface $mealAt, ?string $excludeGroupId = null): ?MealGlucoseResponseData
    {
        $baseline = $this->baselineReading($user, $mealAt);

        if (! $baseline instanceof HealthSyncSample) {
            return null;
        }

        $window = $this->windowReadings($user, $mealAt);

        if ($window->isEmpty()) {
            return null;
        }

        $peak = (float) $window->max(fn (HealthSyncSample $sample): float => $sample->value);

        return new MealGlucoseResponseData(
            baseline: $baseline->value,
            peak: $peak,
            delta: $peak - $baseline->value,
            readingsInWindow: $window->count(),
            overlapping: $this->hasOverlappingMeal($user, $mealAt, $excludeGroupId),
        );
    }

    /**
     * @return list<array{mealAt: CarbonInterface, groupId: string|null, carbs: float|null, response: MealGlucoseResponseData}>
     */
    public function recentResponses(User $user, int $days = 7, int $limit = 10): array
    {
        $meals = HealthSyncSample::query()
            ->where('user_id', $user->id)
            ->whereIn('type_identifier', self::MEAL_TYPES)
            ->where('measured_at', '>=', now()->subDays($days))
            ->latest('measured_at')
            ->get()
            ->groupBy(fn (HealthSyncSample $sample): string => $sample->group_id ?? 'sample_'.$sample->id);

        $responses = [];

        foreach ($meals as $samples) {
            if (count($responses) >= $limit) {
                break;
            }

            /** @var HealthSyncSample $anchor */
            $anchor = $samples->first();
            $response = $this->forMeal($user, $anchor->measured_at, $anchor->group_id);

            if ($response instanceof MealGlucoseResponseData) {
                $carbs = $samples->firstWhere('type_identifier', HealthSyncType::Carbohydrates->value)?->value;

                $responses[] = [
                    'mealAt' => $anchor->measured_at,
                    'groupId' => $anchor->group_id,
                    'carbs' => $carbs,
                    'response' => $response,
                ];
            }
        }

        return $responses;
    }

    public function carbBandPattern(User $user, float $carbs, ?string $excludeGroupId = null, int $days = self::PATTERN_LOOKBACK_DAYS): ?MealGlucosePatternData
    {
        if ($carbs <= 0.0) {
            return null;
        }

        $candidates = HealthSyncSample::query()
            ->where('user_id', $user->id)
            ->where('type_identifier', HealthSyncType::Carbohydrates->value)
            ->whereBetween('value', [$carbs * (1 - self::CARB_BAND_RATIO), $carbs * (1 + self::CARB_BAND_RATIO)])
            ->where('measured_at', '>=', now()->subDays($days))
            ->when($excludeGroupId !== null, fn (Builder $query): Builder => $query->where(
                fn (Builder $inner): Builder => $inner->where('group_id', '!=', $excludeGroupId)->orWhereNull('group_id'),
            ))
            ->latest('measured_at')
            ->limit(self::PATTERN_CANDIDATE_LIMIT)
            ->get();

        $deltas = [];

        foreach ($candidates as $candidate) {
            $response = $this->forMeal($user, $candidate->measured_at, $candidate->group_id);

            if ($response instanceof MealGlucoseResponseData && ! $response->overlapping) {
                $deltas[] = $response->delta;
            }
        }

        if (count($deltas) < self::PATTERN_MIN_MEALS) {
            return null;
        }

        sort($deltas);

        return new MealGlucosePatternData(
            carbs: $carbs,
            median: $this->median($deltas),
            min: $deltas[0],
            max: $deltas[count($deltas) - 1],
            count: count($deltas),
        );
    }

    /**
     * @param  list<float>  $sorted  ascending
     */
    private function median(array $sorted): float
    {
        $count = count($sorted);
        $middle = intdiv($count, 2);

        return $count % 2 === 0
            ? ($sorted[$middle - 1] + $sorted[$middle]) / 2
            : $sorted[$middle];
    }

    private function baselineReading(User $user, CarbonInterface $mealAt): ?HealthSyncSample
    {
        return $this->glucoseQuery($user)
            ->whereBetween('measured_at', [
                $mealAt->copy()->subMinutes(self::BASELINE_WINDOW_MINUTES),
                $mealAt,
            ])
            ->latest('measured_at')
            ->first();
    }

    /**
     * @return Collection<int, HealthSyncSample>
     */
    private function windowReadings(User $user, CarbonInterface $mealAt): Collection
    {
        return $this->glucoseQuery($user)
            ->whereBetween('measured_at', [
                $mealAt->copy()->addMinutes(self::RESPONSE_WINDOW_START_MINUTES),
                $mealAt->copy()->addMinutes(self::RESPONSE_WINDOW_END_MINUTES),
            ])
            ->get();
    }

    private function hasOverlappingMeal(User $user, CarbonInterface $mealAt, ?string $excludeGroupId): bool
    {
        return HealthSyncSample::query()
            ->where('user_id', $user->id)
            ->whereIn('type_identifier', self::MEAL_TYPES)
            ->where('measured_at', '>', $mealAt)
            ->where('measured_at', '<=', $mealAt->copy()->addMinutes(self::RESPONSE_WINDOW_END_MINUTES))
            ->when($excludeGroupId !== null, fn (Builder $query): Builder => $query->where(
                fn (Builder $inner): Builder => $inner->where('group_id', '!=', $excludeGroupId)->orWhereNull('group_id'),
            ))
            ->exists();
    }

    /**
     * @return Builder<HealthSyncSample>
     */
    private function glucoseQuery(User $user): Builder
    {
        return HealthSyncSample::query()
            ->where('user_id', $user->id)
            ->where('type_identifier', HealthSyncType::BloodGlucose->value);
    }
}
