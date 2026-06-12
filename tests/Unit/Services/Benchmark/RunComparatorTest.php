<?php

declare(strict_types=1);

use App\Data\Benchmark\HarnessReport;
use App\Data\Benchmark\MealEvaluation;
use App\Data\Benchmark\PathMetrics;
use App\Data\Benchmark\PredictedRun;
use App\Data\NutrientValues;
use App\Enums\Benchmark\AnalysisPath;
use App\Services\Benchmark\MetricsCalculator;
use App\Services\Benchmark\RunComparator;
use Spatie\LaravelData\DataCollection;

/**
 * @param  list<AnalysisPath>  $paths
 */
function comparisonReport(
    float $predictedCarbs,
    float $truthCarbs = 50.0,
    ?float $itemRecall = null,
    array $paths = [AnalysisPath::Raw, AnalysisPath::Enriched],
): HarnessReport {
    $metrics = (new MetricsCalculator)->calculate([
        new MealEvaluation(
            mealCode: 'm0001',
            mealWeightG: 400,
            truth: new NutrientValues(calories: 400, protein: 20, carbs: $truthCarbs, fat: 10),
            runs: new DataCollection(PredictedRun::class, [
                new PredictedRun(
                    values: new NutrientValues(calories: 400, protein: 20, carbs: $predictedCarbs, fat: 10),
                    confidence: 80,
                    itemRecall: $itemRecall,
                    itemPrecision: $itemRecall,
                ),
            ]),
        ),
    ]);

    return new HarnessReport(
        analyzerVersion: 'test/p1',
        referenceLookupEnabled: true,
        repeats: 1,
        skippedMeals: 0,
        paths: new DataCollection(PathMetrics::class, array_map(
            fn (AnalysisPath $path): PathMetrics => new PathMetrics(path: $path, failedRuns: 0, metrics: $metrics),
            $paths,
        )),
    );
}

beforeEach(function (): void {
    $this->comparator = new RunComparator;
});

it('computes signed metric deltas per path', function (): void {
    $previous = comparisonReport(predictedCarbs: 45.0, itemRecall: 0.5);
    $current = comparisonReport(predictedCarbs: 48.0, itemRecall: 1.0);

    $deltas = $this->comparator->compare($current, $previous);

    expect($deltas)->toHaveCount(2)
        ->and($deltas[0]->path)->toBe(AnalysisPath::Raw)
        ->and($deltas[0]->carbMae)->toEqualWithDelta(-3.0, 0.0001)
        ->and($deltas[0]->carbMape)->toEqualWithDelta(-6.0, 0.0001)
        ->and($deltas[0]->energyMape)->toEqualWithDelta(0.0, 0.0001)
        ->and($deltas[0]->itemRecall)->toEqualWithDelta(0.5, 0.0001)
        ->and($deltas[1]->path)->toBe(AnalysisPath::Enriched);
});

it('propagates null when either side lacks a metric', function (): void {
    $previous = comparisonReport(predictedCarbs: 5.0, truthCarbs: 0.0);
    $current = comparisonReport(predictedCarbs: 48.0);

    $deltas = $this->comparator->compare($current, $previous);

    expect($deltas[0]->carbMae)->toEqualWithDelta(-3.0, 0.0001)
        ->and($deltas[0]->carbMape)->toBeNull()
        ->and($deltas[0]->itemRecall)->toBeNull();
});

it('skips paths the previous run did not measure', function (): void {
    $previous = comparisonReport(predictedCarbs: 45.0, paths: [AnalysisPath::Raw]);
    $current = comparisonReport(predictedCarbs: 48.0);

    $deltas = $this->comparator->compare($current, $previous);

    expect($deltas)->toHaveCount(1)
        ->and($deltas[0]->path)->toBe(AnalysisPath::Raw);
});
