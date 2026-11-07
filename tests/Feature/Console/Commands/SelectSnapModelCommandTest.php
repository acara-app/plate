<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Console\Commands\SelectSnapModelCommand;
use App\Data\Benchmark\BenchmarkMetrics;
use App\Data\Benchmark\HarnessReport;
use App\Data\Benchmark\ItemizationAccuracy;
use App\Data\Benchmark\NutrientError;
use App\Data\Benchmark\PathMetrics;
use App\Enums\Benchmark\AnalysisPath;
use App\Models\BenchmarkRun;
use App\Services\Benchmark\MetricsCalculator;
use Spatie\LaravelData\DataCollection;

covers(SelectSnapModelCommand::class);

function measuredPhotoModelRun(string $model, float $carbsError, float $costUsd = 0.9, int $meals = 30, string $dataset = 'golden-plate-v1'): BenchmarkRun
{
    $empty = new MetricsCalculator()->calculate([]);
    $metrics = new BenchmarkMetrics(
        mealCount: $meals,
        runCount: $meals * 3,
        calories: new NutrientError(mae: 40.0, mape: null),
        carbs: new NutrientError(mae: $carbsError, mape: null),
        protein: $empty->protein,
        fat: $empty->fat,
        macroRatio: $empty->macroRatio,
        portionBias: $empty->portionBias,
        repeatability: $empty->repeatability,
        itemization: new ItemizationAccuracy(recall: 0.9, precision: null, mealsMeasured: $meals),
        calibration: $empty->calibration,
    );
    $report = new HarnessReport(
        analyzerVersion: "{$model}/p".FoodPhotoAnalyzerAgent::PROMPT_VERSION,
        referenceLookupEnabled: true,
        repeats: 3,
        skippedMeals: 0,
        paths: new DataCollection(PathMetrics::class, [
            new PathMetrics(path: AnalysisPath::Raw, failedRuns: 0, metrics: $metrics, costUsd: $costUsd, p95LatencyMs: 12_000.0),
            new PathMetrics(path: AnalysisPath::Enriched, failedRuns: 0, metrics: $metrics, costUsd: $costUsd, p95LatencyMs: 12_000.0),
        ]),
        provider: 'gemini',
        maxTokens: 4096,
        datasetHash: $dataset,
    );

    return BenchmarkRun::factory()->create(['analyzer_version' => $report->analyzerVersion, 'report' => $report->toArray()]);
}

it('recommends the most accurate candidate that clears every release gate', function (): void {
    $baseline = measuredPhotoModelRun('gemini-3.6-flash', carbsError: 6.0);
    $accurate = measuredPhotoModelRun('gemini-3.8-flash', carbsError: 4.5);
    $acceptable = measuredPhotoModelRun('gemini-3.5-flash', carbsError: 5.5);
    $expensive = measuredPhotoModelRun('gemini-3.1-pro-preview', carbsError: 3.0, costUsd: 9.0);

    $this->artisan('snap:select-model', ['baseline' => $baseline->id, 'candidates' => [$acceptable->id, $expensive->id, $accurate->id]])
        ->expectsOutputToContain("Run {$expensive->id} failed the release gates.")
        ->expectsOutputToContain("Selected benchmark run {$accurate->id}.")
        ->expectsOutputToContain('AI_STANDARD_PHOTO_MODEL=gemini-3.8-flash')
        ->expectsOutputToContain('Measured cost per 100 successful scans: $1.0000')
        ->assertSuccessful();
});

it('keeps the current photo model when no candidate clears the release gates', function (int $carbsError, int $meals, string $dataset): void {
    $baseline = measuredPhotoModelRun('gemini-3.6-flash', carbsError: 6.0);
    $candidate = measuredPhotoModelRun('gemini-3.8-flash', carbsError: $carbsError, meals: $meals, dataset: $dataset);

    $this->artisan('snap:select-model', ['baseline' => $baseline->id, 'candidates' => [$candidate->id]])
        ->expectsOutputToContain('No candidate qualifies. Keep using the current photo model.')
        ->assertFailed();
})->with([
    'less accurate than the current model' => [7, 30, 'golden-plate-v1'],
    'measured on too few meals' => [5, 29, 'golden-plate-v1'],
    'measured on a different dataset' => [5, 30, 'golden-plate-v2'],
]);
