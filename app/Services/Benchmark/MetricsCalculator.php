<?php

declare(strict_types=1);

namespace App\Services\Benchmark;

use App\Data\Benchmark\BenchmarkMetrics;
use App\Data\Benchmark\CalibrationBin;
use App\Data\Benchmark\ItemizationAccuracy;
use App\Data\Benchmark\MacroRatioError;
use App\Data\Benchmark\MealEvaluation;
use App\Data\Benchmark\NutrientError;
use App\Data\Benchmark\PortionBiasSlopes;
use App\Data\Benchmark\PredictedRun;
use App\Data\Benchmark\Repeatability;
use App\Data\NutrientValues;
use InvalidArgumentException;
use Spatie\LaravelData\DataCollection;

final class MetricsCalculator
{
    private const array DEFAULT_CONFIDENCE_BIN_EDGES = [0, 50, 60, 70, 80, 90, 100];

    private const array NUTRIENTS = ['calories', 'carbs', 'protein', 'fat'];

    /**
     * @param  list<MealEvaluation>  $evaluations
     * @param  list<int>|null  $confidenceBinEdges
     */
    public function calculate(array $evaluations, ?array $confidenceBinEdges = null): BenchmarkMetrics
    {
        $edges = $confidenceBinEdges ?? self::DEFAULT_CONFIDENCE_BIN_EDGES;

        if (count($edges) < 2) {
            throw new InvalidArgumentException('Confidence bin edges must contain at least two values.');
        }

        $meals = array_values(array_filter(
            $evaluations,
            fn (MealEvaluation $evaluation): bool => $evaluation->runs->count() > 0,
        ));

        $summaries = array_map(fn (MealEvaluation $evaluation): array => $this->summarizeMeal($evaluation), $meals);

        return new BenchmarkMetrics(
            mealCount: count($meals),
            runCount: (int) array_sum(array_column($summaries, 'runCount')),
            calories: $this->nutrientError($summaries, 'calories'),
            carbs: $this->nutrientError($summaries, 'carbs'),
            protein: $this->nutrientError($summaries, 'protein'),
            fat: $this->nutrientError($summaries, 'fat'),
            macroRatio: $this->macroRatioError($summaries),
            portionBias: $this->portionBias($summaries),
            repeatability: $this->repeatability($summaries),
            itemization: $this->itemization($summaries),
            calibration: $this->calibration($summaries, $edges),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function summarizeMeal(MealEvaluation $evaluation): array
    {
        $truth = $evaluation->truth;
        $truthShares = $this->energyShares($truth);

        $absolute = $signed = $percent = $values = array_fill_keys(self::NUTRIENTS, []);
        $ratioDeviations = ['carbs' => [], 'protein' => [], 'fat' => []];
        $calibrationRuns = [];
        $itemRecalls = [];
        $itemPrecisions = [];

        /** @var PredictedRun $run */
        foreach ($evaluation->runs as $run) {
            foreach (self::NUTRIENTS as $nutrient) {
                $predictedValue = $run->values->{$nutrient};
                $truthValue = $truth->{$nutrient};

                $values[$nutrient][] = $predictedValue;
                $signed[$nutrient][] = $predictedValue - $truthValue;
                $absolute[$nutrient][] = abs($predictedValue - $truthValue);

                if ($truthValue > 0.0) {
                    $percent[$nutrient][] = abs($predictedValue - $truthValue) / $truthValue * 100;
                }
            }

            $predictedShares = $this->energyShares($run->values);

            if ($truthShares !== null && $predictedShares !== null) {
                foreach ($ratioDeviations as $macro => $deviations) {
                    $ratioDeviations[$macro][] = abs($predictedShares[$macro] - $truthShares[$macro]);
                }
            }

            $truthCarbs = $truth->carbs;

            $calibrationRuns[] = [
                'confidence' => $run->confidence,
                'absoluteCarbError' => abs($run->values->carbs - $truthCarbs),
                'percentCarbError' => $truthCarbs > 0.0 ? abs($run->values->carbs - $truthCarbs) / $truthCarbs * 100 : null,
            ];

            if ($run->itemRecall !== null) {
                $itemRecalls[] = $run->itemRecall;
            }

            if ($run->itemPrecision !== null) {
                $itemPrecisions[] = $run->itemPrecision;
            }
        }

        $runCount = $evaluation->runs->count();

        return [
            'weight' => $evaluation->mealWeightG,
            'runCount' => $runCount,
            'absoluteMean' => array_map($this->mean(...), $absolute),
            'percentMean' => array_map($this->mean(...), $percent),
            'signedMean' => array_map($this->mean(...), $signed),
            'ratioMean' => array_map($this->mean(...), $ratioDeviations),
            'standardDeviation' => array_map(
                fn (array $runValues): ?float => $runCount >= 2 ? $this->sampleStandardDeviation($runValues) : null,
                $values,
            ),
            'itemRecallMean' => $this->mean($itemRecalls),
            'itemPrecisionMean' => $this->mean($itemPrecisions),
            'calibrationRuns' => $calibrationRuns,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $summaries
     */
    private function itemization(array $summaries): ItemizationAccuracy
    {
        $recalls = $this->pluckValues($summaries, 'itemRecallMean');

        return new ItemizationAccuracy(
            recall: $this->mean($recalls),
            precision: $this->mean($this->pluckValues($summaries, 'itemPrecisionMean')),
            mealsMeasured: count($recalls),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $summaries
     * @return list<float>
     */
    private function pluckValues(array $summaries, string $key): array
    {
        return array_values(array_filter(
            array_map(fn (array $summary): ?float => $summary[$key], $summaries),
            fn (?float $value): bool => $value !== null,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $summaries
     */
    private function nutrientError(array $summaries, string $nutrient): NutrientError
    {
        return new NutrientError(
            mae: $this->mean($this->pluckNutrient($summaries, 'absoluteMean', $nutrient)),
            mape: $this->mean($this->pluckNutrient($summaries, 'percentMean', $nutrient)),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $summaries
     */
    private function macroRatioError(array $summaries): MacroRatioError
    {
        return new MacroRatioError(
            carbsPp: $this->mean($this->pluckNutrient($summaries, 'ratioMean', 'carbs')),
            proteinPp: $this->mean($this->pluckNutrient($summaries, 'ratioMean', 'protein')),
            fatPp: $this->mean($this->pluckNutrient($summaries, 'ratioMean', 'fat')),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $summaries
     */
    private function portionBias(array $summaries): PortionBiasSlopes
    {
        $slopes = [];

        foreach (self::NUTRIENTS as $nutrient) {
            $points = [];

            foreach ($summaries as $summary) {
                $signedMean = $summary['signedMean'][$nutrient];

                if ($signedMean !== null) {
                    $points[] = ['x' => (float) $summary['weight'], 'y' => $signedMean];
                }
            }

            $slopes[$nutrient] = $this->leastSquaresSlope($points);
        }

        return new PortionBiasSlopes(
            calories: $slopes['calories'],
            carbs: $slopes['carbs'],
            protein: $slopes['protein'],
            fat: $slopes['fat'],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $summaries
     */
    private function repeatability(array $summaries): Repeatability
    {
        $measured = array_values(array_filter(
            $summaries,
            fn (array $summary): bool => $summary['standardDeviation']['calories'] !== null,
        ));

        return new Repeatability(
            calories: $this->mean($this->pluckNutrient($measured, 'standardDeviation', 'calories')),
            carbs: $this->mean($this->pluckNutrient($measured, 'standardDeviation', 'carbs')),
            protein: $this->mean($this->pluckNutrient($measured, 'standardDeviation', 'protein')),
            fat: $this->mean($this->pluckNutrient($measured, 'standardDeviation', 'fat')),
            mealsMeasured: count($measured),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $summaries
     * @param  list<int>  $edges
     * @return DataCollection<int, CalibrationBin>
     */
    private function calibration(array $summaries, array $edges): DataCollection
    {
        $runs = array_merge(...array_column($summaries, 'calibrationRuns') ?: [[]]);
        $bins = [];
        $lastIndex = count($edges) - 2;

        for ($i = 0; $i <= $lastIndex; $i++) {
            $min = $edges[$i];
            $max = $edges[$i + 1];

            $inBin = array_values(array_filter(
                $runs,
                fn (array $run): bool => $run['confidence'] >= $min
                    && ($run['confidence'] < $max || ($i === $lastIndex && $run['confidence'] === $max)),
            ));

            $percentErrors = array_values(array_filter(
                array_column($inBin, 'percentCarbError'),
                fn (?float $value): bool => $value !== null,
            ));

            $bins[] = new CalibrationBin(
                minConfidence: $min,
                maxConfidence: $max,
                sampleCount: count($inBin),
                medianAbsoluteCarbError: $this->median(array_column($inBin, 'absoluteCarbError')),
                medianAbsoluteCarbErrorPercent: $this->median($percentErrors),
            );
        }

        return new DataCollection(CalibrationBin::class, $bins);
    }

    /**
     * @return array{carbs: float, protein: float, fat: float}|null
     */
    private function energyShares(NutrientValues $values): ?array
    {
        $energy = 4 * $values->protein + 4 * $values->carbs + 9 * $values->fat;

        if ($energy <= 0.0) {
            return null;
        }

        return [
            'carbs' => 4 * $values->carbs / $energy * 100,
            'protein' => 4 * $values->protein / $energy * 100,
            'fat' => 9 * $values->fat / $energy * 100,
        ];
    }

    /**
     * @param  list<array{x: float, y: float}>  $points
     */
    private function leastSquaresSlope(array $points): ?float
    {
        if (count($points) < 2) {
            return null;
        }

        $meanX = $this->mean(array_column($points, 'x'));
        $meanY = $this->mean(array_column($points, 'y'));

        $covariance = 0.0;
        $variance = 0.0;

        foreach ($points as $point) {
            $covariance += ($point['x'] - $meanX) * ($point['y'] - $meanY);
            $variance += ($point['x'] - $meanX) ** 2;
        }

        return $variance > 0.0 ? $covariance / $variance : null;
    }

    /**
     * @param  list<array<string, mixed>>  $summaries
     * @return list<float>
     */
    private function pluckNutrient(array $summaries, string $key, string $nutrient): array
    {
        return array_values(array_filter(
            array_map(fn (array $summary): ?float => $summary[$key][$nutrient], $summaries),
            fn (?float $value): bool => $value !== null,
        ));
    }

    /**
     * @param  list<float|int>  $values
     */
    private function mean(array $values): ?float
    {
        return $values === [] ? null : array_sum($values) / count($values);
    }

    /**
     * @param  list<float>  $values
     */
    private function sampleStandardDeviation(array $values): float
    {
        $mean = (float) $this->mean($values);
        $squared = array_sum(array_map(fn (float $value): float => ($value - $mean) ** 2, $values));

        return sqrt($squared / (count($values) - 1));
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
        $count = count($values);
        $middle = intdiv($count, 2);

        return $count % 2 === 1
            ? $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
