<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\ReviewedMealData;
use App\Enums\EstimateValidationStatus;
use App\Models\MealEstimate;
use App\Models\MealEstimateDailyFoodChange;
use App\Models\MealEstimateDailyStat;
use App\Utilities\FoodName;
use App\Utilities\Histogram;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class AggregateDailyMealEstimates
{
    public function handle(): void
    {
        $from = CarbonImmutable::now('UTC')->subMonths(MealEstimate::RETENTION_MONTHS)->addDay()->startOfDay();
        $stats = [];
        $foods = [];

        $estimates = MealEstimate::query()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', CarbonImmutable::today('UTC'))
            ->lazyById();

        foreach ($estimates as $estimate) {
            $date = $estimate->created_at->toDateString();
            $model = $estimate->model_version ?? 'unknown';
            $key = "{$date}|{$model}|{$estimate->source->value}";
            $stats[$key] ??= ['date' => $date, 'model_version' => $model, 'source' => $estimate->source->value, 'meals' => 0, 'corrected' => 0, 'kcal_changes' => [], 'kcal_change_percents' => [], 'carbs_changes' => []];
            $stats[$key]['meals']++;

            $correction = $estimate->validation_status === EstimateValidationStatus::UserCorrected ? $estimate->corrected_value : null;

            if ($correction === null) {
                continue;
            }

            $stats[$key]['corrected']++;

            $predicted = $estimate->predictedMeal()['totals'];
            $predictedKcal = (float) ($predicted['calories'] ?? 0);
            $correctedKcal = $this->totalOf($correction, 'calories');
            $correctedCarbs = $this->totalOf($correction, 'carbs');

            if ($correctedKcal !== null) {
                $stats[$key]['kcal_changes'] = Histogram::add($stats[$key]['kcal_changes'], $correctedKcal - $predictedKcal);

                if ($predictedKcal > 0) {
                    $stats[$key]['kcal_change_percents'] = Histogram::add($stats[$key]['kcal_change_percents'], ($correctedKcal - $predictedKcal) / $predictedKcal * 100);
                }
            }

            if ($correctedCarbs !== null) {
                $stats[$key]['carbs_changes'] = Histogram::add($stats[$key]['carbs_changes'], $correctedCarbs - ($predicted['carbs'] ?? 0.0));
            }

            foreach ($this->foodChanges($estimate, $correction) as $pair => [$aiFood, $correctedFood]) {
                $foods["{$date}|{$pair}"] ??= ['date' => $date, 'ai_food' => $aiFood, 'corrected_food' => $correctedFood, 'meals' => 0];
                $foods["{$date}|{$pair}"]['meals']++;
            }
        }

        DB::transaction(function () use ($from, $stats, $foods): void {
            MealEstimateDailyStat::query()->where('date', '>=', $from->toDateString())->delete();
            MealEstimateDailyFoodChange::query()->where('date', '>=', $from->toDateString())->delete();

            $this->insert(MealEstimateDailyStat::class, array_map(fn (array $row): array => [
                ...$row,
                'kcal_changes' => $this->json($row['kcal_changes']),
                'kcal_change_percents' => $this->json($row['kcal_change_percents']),
                'carbs_changes' => $this->json($row['carbs_changes']),
            ], array_values($stats)));
            $this->insert(MealEstimateDailyFoodChange::class, array_values($foods));
        });
    }

    /**
     * @param  array<array-key, mixed>  $correction
     */
    private function totalOf(array $correction, string $nutrient): ?float
    {
        $totals = is_array($correction['totals'] ?? null) ? $correction['totals'] : [];

        return is_numeric($totals[$nutrient] ?? null) ? (float) $totals[$nutrient] : null;
    }

    /**
     * @param  array<array-key, mixed>  $correction
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    private function foodChanges(MealEstimate $estimate, array $correction): array
    {
        if (! is_array($correction['items'] ?? null)) {
            return [];
        }

        $remaining = array_map(
            fn (array $item): string => FoodName::normalize(is_string($item['name'] ?? null) ? $item['name'] : ''),
            ReviewedMealData::normalizeItems($correction['items']),
        );
        $unmatched = [];

        foreach (ReviewedMealData::normalizeItems($estimate->original_prediction['items'] ?? null) as $item) {
            $name = is_string($item['name'] ?? null) ? $item['name'] : '';
            $match = array_search(FoodName::normalize($name), $remaining, true);

            if ($match === false) {
                $unmatched[] = FoodName::normalize(is_string($item['match_name'] ?? null) ? $item['match_name'] : $name);

                continue;
            }

            unset($remaining[$match]);
        }

        $remaining = array_values($remaining);
        $changes = [];

        for ($index = 0; $index < max(count($unmatched), count($remaining)); $index++) {
            $pair = [$unmatched[$index] ?? null, $remaining[$index] ?? null];

            if (! in_array('', $pair, true)) {
                $changes[implode('|', $pair)] = $pair;
            }
        }

        return $changes;
    }

    /**
     * @param  class-string<MealEstimateDailyStat|MealEstimateDailyFoodChange>  $model
     * @param  list<array<string, mixed>>  $rows
     */
    private function insert(string $model, array $rows): void
    {
        $now = now();

        foreach (array_chunk($rows, 500) as $chunk) {
            $model::query()->insert(array_map(fn (array $row): array => [...$row, 'created_at' => $now, 'updated_at' => $now], $chunk));
        }
    }

    /**
     * @param  array<int, int>  $counts
     */
    private function json(array $counts): string
    {
        return json_encode($counts, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
    }
}
