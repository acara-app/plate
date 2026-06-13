<?php

declare(strict_types=1);

namespace App\Data\Benchmark;

use App\Data\NutrientValues;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class MealEvaluation extends Data
{
    /**
     * @param  DataCollection<int, PredictedRun>  $runs
     */
    public function __construct(
        public string $mealCode,
        public float $mealWeightG,
        public NutrientValues $truth,
        #[DataCollectionOf(PredictedRun::class)]
        public DataCollection $runs,
    ) {}
}
