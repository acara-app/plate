<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\MealEstimateReportData;
use App\Data\ReviewedMealData;
use App\Enums\EstimateValidationStatus;
use App\Models\MealEstimate;
use App\Utilities\FoodName;
use Carbon\CarbonImmutable;

final readonly class BuildMealEstimateReport
{
    public const int MIN_MEALS = 10;

    private const int MAX_FOOD_ROWS = 20;

    public function handle(CarbonImmutable $from, CarbonImmutable $to): MealEstimateReportData
    {
        $groups = [];
        $renamed = [];
        $changed = [];
        $meals = 0;
        $corrected = 0;

        foreach (MealEstimate::query()->whereBetween('created_at', [$from, $to])->lazyById() as $estimate) {
            $model = $estimate->model_version ?? 'unknown';
            $key = "{$model}|{$estimate->source->value}";
            $groups[$key] ??= ['model' => $model, 'source' => $estimate->source->value, 'meals' => 0, 'corrected' => 0, 'kcal' => [], 'kcalPercent' => [], 'carbs' => []];
            $groups[$key]['meals']++;
            $meals++;

            $correction = $estimate->validation_status === EstimateValidationStatus::UserCorrected ? $estimate->corrected_value : null;

            if ($correction === null) {
                continue;
            }

            $groups[$key]['corrected']++;
            $corrected++;

            $predicted = $estimate->predictedMeal()['totals'];
            $predictedKcal = (float) ($predicted['calories'] ?? 0);
            $correctedKcal = $this->totalOf($correction, 'calories');
            $correctedCarbs = $this->totalOf($correction, 'carbs');

            if ($correctedKcal !== null) {
                $groups[$key]['kcal'][] = $correctedKcal - $predictedKcal;

                if ($predictedKcal > 0) {
                    $groups[$key]['kcalPercent'][] = ($correctedKcal - $predictedKcal) / $predictedKcal * 100;
                }
            }

            if ($correctedCarbs !== null) {
                $groups[$key]['carbs'][] = $correctedCarbs - ($predicted['carbs'] ?? 0.0);
            }

            $foods = $this->foodChanges($estimate, $correction);

            foreach (array_unique(array_map(fn (array $pair): string => implode('|', $pair), $foods['renamed'])) as $pair) {
                [$aiFood, $correctedTo] = explode('|', $pair, 2);
                $renamed[$pair] ??= ['aiFood' => $aiFood, 'correctedTo' => $correctedTo, 'meals' => 0];
                $renamed[$pair]['meals']++;
            }

            foreach (['added' => $foods['added'], 'removed' => $foods['removed']] as $change => $names) {
                foreach (array_unique($names) as $name) {
                    $changed["{$change}|{$name}"] ??= ['change' => $change, 'food' => $name, 'meals' => 0];
                    $changed["{$change}|{$name}"]['meals']++;
                }
            }
        }

        $shown = array_filter($groups, fn (array $group): bool => $group['meals'] >= self::MIN_MEALS);

        return new MealEstimateReportData(
            from: $from->toIso8601String(),
            to: $to->toIso8601String(),
            minimumMeals: self::MIN_MEALS,
            meals: $meals,
            corrected: $corrected,
            hiddenModelGroups: count($groups) - count($shown),
            models: $this->modelRows($shown),
            renamedFoods: array_map(
                fn (string $key): array => $renamed[$key],
                $this->topKeys(array_map(fn (array $row): int => $row['meals'], $renamed)),
            ),
            changedFoods: array_map(
                fn (string $key): array => $changed[$key],
                $this->topKeys(array_map(fn (array $row): int => $row['meals'], $changed)),
            ),
        );
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
     * @return array{renamed: list<array{0: string, 1: string}>, added: list<string>, removed: list<string>}
     */
    private function foodChanges(MealEstimate $estimate, array $correction): array
    {
        if (! is_array($correction['items'] ?? null)) {
            return ['renamed' => [], 'added' => [], 'removed' => []];
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
        $pairs = min(count($unmatched), count($remaining));
        $renamed = [];

        for ($index = 0; $index < $pairs; $index++) {
            $renamed[] = [$unmatched[$index], $remaining[$index]];
        }

        return [
            'renamed' => array_values(array_filter($renamed, fn (array $pair): bool => $pair[0] !== '' && $pair[1] !== '')),
            'added' => array_values(array_filter(array_slice($remaining, $pairs), fn (string $name): bool => $name !== '')),
            'removed' => array_values(array_filter(array_slice($unmatched, $pairs), fn (string $name): bool => $name !== '')),
        ];
    }

    /**
     * @param  array<string, array{model: string, source: string, meals: int, corrected: int, kcal: list<float>, kcalPercent: list<float>, carbs: list<float>}>  $groups
     * @return list<array{model: string, source: string, meals: int, corrected: int, medianKcalChange: float|null, medianKcalChangePercent: float|null, medianCarbsChange: float|null}>
     */
    private function modelRows(array $groups): array
    {
        $rows = array_map(fn (array $group): array => [
            'model' => $group['model'],
            'source' => $group['source'],
            'meals' => $group['meals'],
            'corrected' => $group['corrected'],
            'medianKcalChange' => $this->median($group['kcal']),
            'medianKcalChangePercent' => $this->median($group['kcalPercent']),
            'medianCarbsChange' => $this->median($group['carbs']),
        ], array_values($groups));

        usort($rows, fn (array $a, array $b): int => [$b['meals'], $a['model'], $a['source']] <=> [$a['meals'], $b['model'], $b['source']]);

        return $rows;
    }

    /**
     * @param  array<string, int>  $meals
     * @return list<string>
     */
    private function topKeys(array $meals): array
    {
        $shown = array_filter($meals, fn (int $count): bool => $count >= self::MIN_MEALS);

        arsort($shown);

        return array_slice(array_map(strval(...), array_keys($shown)), 0, self::MAX_FOOD_ROWS);
    }

    /**
     * @param  list<float>  $values
     */
    private function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $middle = intdiv(count($values), 2);

        return count($values) % 2 === 1 ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
