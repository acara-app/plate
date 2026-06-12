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

/**
 * @phpstan-type CalibrationRun array{confidence: int, absoluteCarbError: float, percentCarbError: float|null}
 * @phpstan-type MealSummary array{weight: float, runCount: int, absoluteMean: array<string, float|null>, percentMean: array<string, float|null>, signedMean: array<string, float|null>, ratioMean: array<string, float|null>, standardDeviation: array<string, float|null>, itemRecallMean: float|null, itemPrecisionMean: float|null, calibrationRuns: list<CalibrationRun>}
 */
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

        throw_if(count($edges) < 2, InvalidArgumentException::class, 'Confidence bin edges must contain at least two values.');

        $meals = array_values(array_filter(
            $evaluations,
            fn (MealEvaluation $evaluation): bool => $evaluation->runs->count() > 0,
        ));

        $summaries = array_map($this->summarizeMeal(...), $meals);

        return new BenchmarkMetrics(
            mealCount: count($meals),
            runCount: array_sum(array_column($summaries, 'runCount')),
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
     * @return MealSummary
     */
    private function summarizeMeal(MealEvaluation $evaluation): array
    {
        $truth = $evaluation->truth;
        $truthShares = $this->energyShares($truth);
        $absolute = array_fill_keys(self::NUTRIENTS, []);
        $signed = $absolute;
        $percent = $absolute;
        $values = $absolute;
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
                foreach (array_keys($ratioDeviations) as $macro) {
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
            'standardDeviation' => $runCount >= 2
                ? array_map($this->sampleStandardDeviation(...), $values)
                : array_fill_keys(self::NUTRIENTS, null),
            'itemRecallMean' => $this->mean($itemRecalls),
            'itemPrecisionMean' => $this->mean($itemPrecisions),
            'calibrationRuns' => $calibrationRuns,
        ];
    }

    /**
     * @param  list<MealSummary>  $summaries
     */
    private function itemization(array $summaries): ItemizationAccuracy
    {
        $recalls = $this->definedFloats(array_column($summaries, 'itemRecallMean'));

        return new ItemizationAccuracy(
            recall: $this->mean($recalls),
            precision: $this->mean($this->definedFloats(array_column($summaries, 'itemPrecisionMean'))),
            mealsMeasured: count($recalls),
        );
    }

    /**
     * @param  list<MealSummary>  $summaries
     */
    private function nutrientError(array $summaries, string $nutrient): NutrientError
    {
        return new NutrientError(
            mae: $this->mean($this->definedColumn(array_column($summaries, 'absoluteMean'), $nutrient)),
            mape: $this->mean($this->definedColumn(array_column($summaries, 'percentMean'), $nutrient)),
        );
    }

    /**
     * @param  list<MealSummary>  $summaries
     */
    private function macroRatioError(array $summaries): MacroRatioError
    {
        $ratios = array_column($summaries, 'ratioMean');

        return new MacroRatioError(
            carbsPp: $this->mean($this->definedColumn($ratios, 'carbs')),
            proteinPp: $this->mean($this->definedColumn($ratios, 'protein')),
            fatPp: $this->mean($this->definedColumn($ratios, 'fat')),
        );
    }

    /**
     * @param  list<MealSummary>  $summaries
     */
    private function portionBias(array $summaries): PortionBiasSlopes
    {
        $slopes = [];

        foreach (self::NUTRIENTS as $nutrient) {
            $points = [];

            foreach ($summaries as $summary) {
                $signedMean = $summary['signedMean'][$nutrient] ?? null;

                if ($signedMean !== null) {
                    $points[] = ['x' => $summary['weight'], 'y' => $signedMean];
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
     * @param  list<MealSummary>  $summaries
     */
    private function repeatability(array $summaries): Repeatability
    {
        $deviations = [];

        foreach ($summaries as $summary) {
            if ($summary['standardDeviation']['calories'] !== null) {
                $deviations[] = $summary['standardDeviation'];
            }
        }

        return new Repeatability(
            calories: $this->mean($this->definedColumn($deviations, 'calories')),
            carbs: $this->mean($this->definedColumn($deviations, 'carbs')),
            protein: $this->mean($this->definedColumn($deviations, 'protein')),
            fat: $this->mean($this->definedColumn($deviations, 'fat')),
            mealsMeasured: count($deviations),
        );
    }

    /**
     * @param  list<MealSummary>  $summaries
     * @param  list<int>  $edges
     * @return DataCollection<int, CalibrationBin>
     */
    private function calibration(array $summaries, array $edges): DataCollection
    {
        $runs = [];

        foreach ($summaries as $summary) {
            foreach ($summary['calibrationRuns'] as $run) {
                $runs[] = $run;
            }
        }

        $bins = [];
        $lastIndex = count($edges) - 2;

        for ($i = 0; $i <= $lastIndex; $i++) {
            $min = $edges[$i];
            $max = $edges[$i + 1];

            $inBin = [];

            foreach ($runs as $run) {
                $confidence = $run['confidence'];

                if ($confidence >= $min && ($confidence < $max || ($i === $lastIndex && $confidence === $max))) {
                    $inBin[] = $run;
                }
            }

            $bins[] = new CalibrationBin(
                minConfidence: $min,
                maxConfidence: $max,
                sampleCount: count($inBin),
                medianAbsoluteCarbError: $this->median(array_column($inBin, 'absoluteCarbError')),
                medianAbsoluteCarbErrorPercent: $this->median($this->definedFloats(array_column($inBin, 'percentCarbError'))),
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

        $meanX = (float) $this->mean(array_column($points, 'x'));
        $meanY = (float) $this->mean(array_column($points, 'y'));

        $covariance = 0.0;
        $variance = 0.0;

        foreach ($points as $point) {
            $covariance += ($point['x'] - $meanX) * ($point['y'] - $meanY);
            $variance += ($point['x'] - $meanX) ** 2;
        }

        return $variance > 0.0 ? $covariance / $variance : null;
    }

    /**
     * @param  list<array<string, float|null>>  $maps
     * @return list<float>
     */
    private function definedColumn(array $maps, string $nutrient): array
    {
        $defined = [];

        foreach ($maps as $map) {
            $value = $map[$nutrient] ?? null;

            if ($value !== null) {
                $defined[] = $value;
            }
        }

        return $defined;
    }

    /**
     * @param  list<float|null>  $values
     * @return list<float>
     */
    private function definedFloats(array $values): array
    {
        $defined = [];

        foreach ($values as $value) {
            if ($value !== null) {
                $defined[] = $value;
            }
        }

        return $defined;
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
        if (count($values) < 2) {
            return 0.0;
        }

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
