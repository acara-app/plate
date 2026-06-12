<?php

declare(strict_types=1);

use App\Data\Benchmark\BenchmarkMetrics;
use App\Data\Benchmark\CalibrationBin;
use App\Data\Benchmark\MealEvaluation;
use App\Data\Benchmark\PredictedRun;
use App\Data\NutrientValues;
use App\Services\Benchmark\MetricsCalculator;
use Spatie\LaravelData\DataCollection;

function benchmarkNutrients(float $calories, float $protein, float $carbs, float $fat): NutrientValues
{
    return new NutrientValues(calories: $calories, protein: $protein, carbs: $carbs, fat: $fat);
}

/**
 * @param  list<array{NutrientValues, int}>  $runs
 */
function benchmarkMeal(string $code, float $weight, NutrientValues $truth, array $runs): MealEvaluation
{
    return new MealEvaluation(
        mealCode: $code,
        mealWeightG: $weight,
        truth: $truth,
        runs: new DataCollection(PredictedRun::class, array_map(
            fn (array $run): PredictedRun => new PredictedRun(
                values: $run[0],
                confidence: $run[1],
                itemRecall: $run[2] ?? null,
                itemPrecision: $run[3] ?? null,
            ),
            $runs,
        )),
    );
}

function calibrationBin(BenchmarkMetrics $metrics, int $minConfidence): CalibrationBin
{
    foreach ($metrics->calibration as $bin) {
        if ($bin->minConfidence === $minConfidence) {
            return $bin;
        }
    }

    throw new RuntimeException(sprintf('No calibration bin starting at %d.', $minConfidence));
}

beforeEach(function (): void {
    $this->calculator = new MetricsCalculator;
});

it('returns an empty-but-stable shape when there are no evaluations', function (): void {
    $metrics = $this->calculator->calculate([]);

    expect($metrics->mealCount)->toBe(0)
        ->and($metrics->runCount)->toBe(0)
        ->and($metrics->carbs->mae)->toBeNull()
        ->and($metrics->carbs->mape)->toBeNull()
        ->and($metrics->macroRatio->carbsPp)->toBeNull()
        ->and($metrics->portionBias->calories)->toBeNull()
        ->and($metrics->repeatability->mealsMeasured)->toBe(0)
        ->and($metrics->itemization->recall)->toBeNull()
        ->and($metrics->itemization->mealsMeasured)->toBe(0)
        ->and($metrics->calibration)->toHaveCount(6)
        ->and(calibrationBin($metrics, 0)->sampleCount)->toBe(0)
        ->and(calibrationBin($metrics, 0)->medianAbsoluteCarbError)->toBeNull()
        ->and(calibrationBin($metrics, 90)->maxConfidence)->toBe(100);
});

it('reports zero error everywhere for perfect predictions', function (): void {
    $truthA = benchmarkNutrients(400, 20, 50, 10);
    $truthB = benchmarkNutrients(600, 30, 80, 15);

    $metrics = $this->calculator->calculate([
        benchmarkMeal('m0001', 300, $truthA, [[$truthA, 90], [$truthA, 80]]),
        benchmarkMeal('m0002', 600, $truthB, [[$truthB, 90], [$truthB, 80]]),
    ]);

    expect($metrics->mealCount)->toBe(2)
        ->and($metrics->runCount)->toBe(4)
        ->and($metrics->carbs->mae)->toBe(0.0)
        ->and($metrics->carbs->mape)->toBe(0.0)
        ->and($metrics->calories->mae)->toBe(0.0)
        ->and($metrics->macroRatio->carbsPp)->toBe(0.0)
        ->and($metrics->macroRatio->fatPp)->toBe(0.0)
        ->and($metrics->portionBias->calories)->toBe(0.0)
        ->and($metrics->portionBias->carbs)->toBe(0.0)
        ->and($metrics->repeatability->carbs)->toBe(0.0)
        ->and($metrics->repeatability->mealsMeasured)->toBe(2)
        ->and(calibrationBin($metrics, 90)->sampleCount)->toBe(2)
        ->and(calibrationBin($metrics, 90)->medianAbsoluteCarbError)->toBe(0.0)
        ->and(calibrationBin($metrics, 80)->sampleCount)->toBe(2);
});

it('macro-averages MAE and MAPE so every meal counts equally regardless of repeats', function (): void {
    $metrics = $this->calculator->calculate([
        benchmarkMeal('m0001', 400, benchmarkNutrients(300, 10, 50, 5), [
            [benchmarkNutrients(300, 10, 40, 5), 80],
            [benchmarkNutrients(300, 10, 80, 5), 80],
        ]),
        benchmarkMeal('m0002', 500, benchmarkNutrients(500, 20, 100, 10), [
            [benchmarkNutrients(500, 20, 110, 10), 80],
        ]),
    ]);

    expect($metrics->carbs->mae)->toEqualWithDelta(15.0, 0.0001)
        ->and($metrics->carbs->mape)->toEqualWithDelta(25.0, 0.0001);
});

it('keeps zero-truth meals in MAE but excludes them from MAPE and ratio error', function (): void {
    $metrics = $this->calculator->calculate([
        benchmarkMeal('m0001', 400, benchmarkNutrients(0, 0, 0, 0), [
            [benchmarkNutrients(40, 0, 10, 0), 80],
        ]),
    ]);

    expect($metrics->carbs->mae)->toBe(10.0)
        ->and($metrics->carbs->mape)->toBeNull()
        ->and($metrics->macroRatio->carbsPp)->toBeNull();
});

it('measures macro-ratio error as percentage-point deviation of energy shares', function (): void {
    $metrics = $this->calculator->calculate([
        benchmarkMeal('m0001', 400, benchmarkNutrients(200, 10, 40, 0), [
            [benchmarkNutrients(200, 20, 30, 0), 80],
        ]),
    ]);

    expect($metrics->macroRatio->carbsPp)->toEqualWithDelta(20.0, 0.0001)
        ->and($metrics->macroRatio->proteinPp)->toEqualWithDelta(20.0, 0.0001)
        ->and($metrics->macroRatio->fatPp)->toEqualWithDelta(0.0, 0.0001);
});

it('fits the portion-bias slope of signed error against meal weight', function (): void {
    $metrics = $this->calculator->calculate([
        benchmarkMeal('m0001', 300, benchmarkNutrients(100, 10, 20, 5), [
            [benchmarkNutrients(90, 10, 20, 5), 80],
        ]),
        benchmarkMeal('m0002', 600, benchmarkNutrients(200, 20, 40, 10), [
            [benchmarkNutrients(160, 20, 40, 10), 80],
        ]),
    ]);

    expect($metrics->portionBias->calories)->toEqualWithDelta(-0.1, 0.000001)
        ->and($metrics->portionBias->carbs)->toEqualWithDelta(0.0, 0.000001);
});

it('returns a null slope when meal weights do not vary', function (): void {
    $truth = benchmarkNutrients(100, 10, 20, 5);

    $metrics = $this->calculator->calculate([
        benchmarkMeal('m0001', 400, $truth, [[benchmarkNutrients(90, 10, 20, 5), 80]]),
        benchmarkMeal('m0002', 400, $truth, [[benchmarkNutrients(120, 10, 20, 5), 80]]),
    ]);

    expect($metrics->portionBias->calories)->toBeNull();
});

it('measures run-to-run standard deviation only on meals with repeats', function (): void {
    $metrics = $this->calculator->calculate([
        benchmarkMeal('m0001', 400, benchmarkNutrients(300, 10, 50, 5), [
            [benchmarkNutrients(300, 10, 40, 5), 80],
            [benchmarkNutrients(300, 10, 70, 5), 80],
        ]),
        benchmarkMeal('m0002', 500, benchmarkNutrients(500, 20, 100, 10), [
            [benchmarkNutrients(500, 20, 110, 10), 80],
        ]),
    ]);

    expect($metrics->repeatability->mealsMeasured)->toBe(1)
        ->and($metrics->repeatability->carbs)->toEqualWithDelta(21.2132, 0.0001)
        ->and($metrics->repeatability->calories)->toBe(0.0);
});

it('bins calibration runs by confidence with medians, inclusive of the top edge', function (): void {
    $truth = benchmarkNutrients(300, 10, 50, 5);

    $metrics = $this->calculator->calculate([
        benchmarkMeal('m0001', 400, $truth, [
            [benchmarkNutrients(300, 10, 45, 5), 85],
            [benchmarkNutrients(300, 10, 65, 5), 88],
            [benchmarkNutrients(300, 10, 57, 5), 92],
            [benchmarkNutrients(300, 10, 50, 5), 100],
            [benchmarkNutrients(300, 10, 50, 5), 55],
        ]),
    ]);

    $eighties = calibrationBin($metrics, 80);
    $nineties = calibrationBin($metrics, 90);

    expect($eighties->sampleCount)->toBe(2)
        ->and($eighties->medianAbsoluteCarbError)->toEqualWithDelta(10.0, 0.0001)
        ->and($eighties->medianAbsoluteCarbErrorPercent)->toEqualWithDelta(20.0, 0.0001)
        ->and($nineties->sampleCount)->toBe(2)
        ->and($nineties->medianAbsoluteCarbError)->toEqualWithDelta(3.5, 0.0001)
        ->and(calibrationBin($metrics, 50)->sampleCount)->toBe(1)
        ->and(calibrationBin($metrics, 0)->sampleCount)->toBe(0)
        ->and(calibrationBin($metrics, 0)->medianAbsoluteCarbError)->toBeNull();
});

it('aggregates itemization scores only over meals that carry them', function (): void {
    $truth = benchmarkNutrients(300, 10, 50, 5);

    $metrics = $this->calculator->calculate([
        benchmarkMeal('m0001', 400, $truth, [
            [benchmarkNutrients(300, 10, 50, 5), 80, 1.0, 1.0],
            [benchmarkNutrients(300, 10, 50, 5), 80, 0.5, 1.0],
        ]),
        benchmarkMeal('m0002', 500, $truth, [
            [benchmarkNutrients(300, 10, 50, 5), 80],
        ]),
    ]);

    expect($metrics->itemization->recall)->toEqualWithDelta(0.75, 0.0001)
        ->and($metrics->itemization->precision)->toEqualWithDelta(1.0, 0.0001)
        ->and($metrics->itemization->mealsMeasured)->toBe(1);
});

it('skips meals without runs entirely', function (): void {
    $truth = benchmarkNutrients(300, 10, 50, 5);

    $metrics = $this->calculator->calculate([
        benchmarkMeal('m0001', 400, $truth, []),
        benchmarkMeal('m0002', 500, $truth, [[benchmarkNutrients(300, 10, 60, 5), 80]]),
    ]);

    expect($metrics->mealCount)->toBe(1)
        ->and($metrics->runCount)->toBe(1)
        ->and($metrics->carbs->mae)->toBe(10.0);
});
