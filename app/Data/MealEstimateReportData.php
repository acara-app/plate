<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class MealEstimateReportData extends Data
{
    /**
     * @param  list<array{model: string, source: string, meals: int, corrected: int, medianKcalChange: float|null, medianKcalChangePercent: float|null, medianCarbsChange: float|null}>  $models
     * @param  list<array{aiFood: string, correctedTo: string, meals: int}>  $renamedFoods
     * @param  list<array{change: string, food: string, meals: int}>  $changedFoods
     */
    public function __construct(
        public string $from,
        public string $to,
        public int $minimumMeals,
        public int $meals,
        public int $corrected,
        public int $hiddenModelGroups,
        public array $models,
        public array $renamedFoods,
        public array $changedFoods,
    ) {}
}
