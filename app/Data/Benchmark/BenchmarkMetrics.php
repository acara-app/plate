<?php

declare(strict_types=1);

namespace App\Data\Benchmark;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class BenchmarkMetrics extends Data
{
    /**
     * @param  DataCollection<int, CalibrationBin>  $calibration
     */
    public function __construct(
        public int $mealCount,
        public int $runCount,
        public NutrientError $calories,
        public NutrientError $carbs,
        public NutrientError $protein,
        public NutrientError $fat,
        public MacroRatioError $macroRatio,
        public PortionBiasSlopes $portionBias,
        public Repeatability $repeatability,
        public ItemizationAccuracy $itemization,
        #[DataCollectionOf(CalibrationBin::class)]
        public DataCollection $calibration,
    ) {}
}
