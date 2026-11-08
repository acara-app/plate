<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\MealEstimateReportData;
use App\Models\MealEstimateDailyFoodChange;
use App\Models\MealEstimateDailyStat;
use App\Utilities\Histogram;
use Carbon\CarbonImmutable;

final readonly class BuildMealEstimateReport
{
    public const int MIN_MEALS = 10;

    private const int MAX_FOOD_ROWS = 20;

    public function handle(CarbonImmutable $from, CarbonImmutable $to): MealEstimateReportData
    {
        $days = [$from->toDateString(), $to->toDateString()];
        $groups = [];

        foreach (MealEstimateDailyStat::query()->whereBetween('date', $days)->get() as $day) {
            $key = "{$day->model_version}|{$day->source->value}";
            $groups[$key] ??= ['model' => $day->model_version, 'source' => $day->source->value, 'meals' => 0, 'corrected' => 0, 'kcal' => [], 'kcalPercent' => [], 'carbs' => []];
            $groups[$key]['meals'] += $day->meals;
            $groups[$key]['corrected'] += $day->corrected;
            $groups[$key]['kcal'] = Histogram::merge($groups[$key]['kcal'], $day->kcal_changes);
            $groups[$key]['kcalPercent'] = Histogram::merge($groups[$key]['kcalPercent'], $day->kcal_change_percents);
            $groups[$key]['carbs'] = Histogram::merge($groups[$key]['carbs'], $day->carbs_changes);
        }

        $shown = array_filter($groups, fn (array $group): bool => $group['meals'] >= self::MIN_MEALS);
        $foods = $this->foodRows($days);

        return new MealEstimateReportData(
            from: $days[0],
            to: $days[1],
            minimumMeals: self::MIN_MEALS,
            meals: array_sum(array_column($groups, 'meals')),
            corrected: array_sum(array_column($groups, 'corrected')),
            hiddenModelGroups: count($groups) - count($shown),
            models: $this->modelRows($shown),
            renamedFoods: $foods['renamed'],
            changedFoods: $foods['changed'],
        );
    }

    /**
     * @param  array<string, array{model: string, source: string, meals: int, corrected: int, kcal: array<int, int>, kcalPercent: array<int, int>, carbs: array<int, int>}>  $groups
     * @return list<array{model: string, source: string, meals: int, corrected: int, medianKcalChange: float|null, medianKcalChangePercent: float|null, medianCarbsChange: float|null}>
     */
    private function modelRows(array $groups): array
    {
        $rows = array_map(fn (array $group): array => [
            'model' => $group['model'],
            'source' => $group['source'],
            'meals' => $group['meals'],
            'corrected' => $group['corrected'],
            'medianKcalChange' => Histogram::median($group['kcal']),
            'medianKcalChangePercent' => Histogram::median($group['kcalPercent']),
            'medianCarbsChange' => Histogram::median($group['carbs']),
        ], array_values($groups));

        usort($rows, fn (array $a, array $b): int => [$b['meals'], $a['model'], $a['source']] <=> [$a['meals'], $b['model'], $b['source']]);

        return $rows;
    }

    /**
     * @param  array{0: string, 1: string}  $days
     * @return array{renamed: list<array{aiFood: string, correctedTo: string, meals: int}>, changed: list<array{change: string, food: string, meals: int}>}
     */
    private function foodRows(array $days): array
    {
        $renamed = [];
        $changed = [];

        $foods = MealEstimateDailyFoodChange::query()
            ->select(['ai_food', 'corrected_food'])
            ->selectRaw('sum(meals) as meals')
            ->whereBetween('date', $days)
            ->groupBy('ai_food', 'corrected_food')
            ->havingRaw('sum(meals) >= ?', [self::MIN_MEALS])
            ->orderByRaw('sum(meals) desc')
            ->orderBy('ai_food')
            ->orderBy('corrected_food')
            ->get();

        foreach ($foods as $food) {
            if ($food->ai_food !== null && $food->corrected_food !== null) {
                $renamed[] = ['aiFood' => $food->ai_food, 'correctedTo' => $food->corrected_food, 'meals' => $food->meals];

                continue;
            }

            $changed[] = [
                'change' => $food->ai_food === null ? 'added' : 'removed',
                'food' => $food->ai_food ?? (string) $food->corrected_food,
                'meals' => $food->meals,
            ];
        }

        return [
            'renamed' => array_slice($renamed, 0, self::MAX_FOOD_ROWS),
            'changed' => array_slice($changed, 0, self::MAX_FOOD_ROWS),
        ];
    }
}
