<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Data\Benchmark\HarnessReport;
use App\Data\Benchmark\MealEvaluation;
use App\Data\Benchmark\PathMetrics;
use App\Data\Benchmark\PredictedRun;
use App\Data\NutrientValues;
use App\Enums\Benchmark\AnalysisPath;
use App\Models\BenchmarkMeal;
use App\Models\BenchmarkMealItem;
use App\Models\BenchmarkRun;
use App\Services\Benchmark\MetricsCalculator;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\DataCollection;

beforeEach(function (): void {
    config()->set('plate.benchmark.photo_disk', 'local');
    Storage::fake('local');
});

function seedGoldenPlate(): BenchmarkMeal
{
    $meal = BenchmarkMeal::factory()->create([
        'code' => 'm0001',
        'total_weight_g' => 428,
        'photo_disk' => 'local',
        'photo_path' => 'benchmark/golden-plates/m0001.jpg',
    ]);

    BenchmarkMealItem::factory()->create([
        'benchmark_meal_id' => $meal->id,
        'position' => 1,
        'name' => 'chicken breast, grilled',
        'weight_g' => 150,
        'kcal_per_100g' => 165,
        'carbs_per_100g' => 0,
        'protein_per_100g' => 31,
        'fat_per_100g' => 3.6,
    ]);

    BenchmarkMealItem::factory()->create([
        'benchmark_meal_id' => $meal->id,
        'position' => 2,
        'name' => 'rice, white, cooked',
        'weight_g' => 278,
        'kcal_per_100g' => 130,
        'carbs_per_100g' => 28.2,
        'protein_per_100g' => 2.7,
        'fat_per_100g' => 0.3,
    ]);

    Storage::disk('local')->put('benchmark/golden-plates/m0001.jpg', 'fake-image-bytes');

    return $meal;
}

/**
 * @return array<string, mixed>
 */
function perfectAnalysisPayload(): array
{
    return [
        'items' => [
            ['name' => 'chicken breast, grilled', 'calories' => 247.5, 'protein' => 46.5, 'carbs' => 0.0, 'fat' => 5.4, 'portion' => '150g', 'grams' => 150.0, 'match_name' => 'chicken breast, grilled'],
            ['name' => 'rice, white, cooked', 'calories' => 361.4, 'protein' => 7.5, 'carbs' => 78.4, 'fat' => 0.8, 'portion' => '278g', 'grams' => 278.0, 'match_name' => 'rice, white, cooked'],
        ],
        'total_calories' => 608.9,
        'total_protein' => 54.0,
        'total_carbs' => 78.4,
        'total_fat' => 6.2,
        'confidence' => 90,
    ];
}

/**
 * @return array<string, mixed>
 */
function reportWithCarbError(float $absoluteError): array
{
    $metrics = (new MetricsCalculator)->calculate([
        new MealEvaluation(
            mealCode: 'm0001',
            mealWeightG: 428,
            truth: new NutrientValues(calories: 608.9, protein: 54.0, carbs: 78.4, fat: 6.2),
            runs: new DataCollection(PredictedRun::class, [
                new PredictedRun(
                    values: new NutrientValues(calories: 608.9, protein: 54.0, carbs: 78.4 + $absoluteError, fat: 6.2),
                    confidence: 90,
                    itemRecall: 1.0,
                    itemPrecision: 1.0,
                ),
            ]),
        ),
    ]);

    return new HarnessReport(
        analyzerVersion: 'gemini-3-flash-preview/p2',
        referenceLookupEnabled: true,
        repeats: 5,
        skippedMeals: 0,
        paths: new DataCollection(PathMetrics::class, [
            new PathMetrics(path: AnalysisPath::Raw, failedRuns: 0, metrics: $metrics),
            new PathMetrics(path: AnalysisPath::Enriched, failedRuns: 0, metrics: $metrics),
        ]),
    )->toArray();
}

it('benchmarks both production paths, reports per-path metrics, and persists the run', function (): void {
    seedGoldenPlate();
    FoodPhotoAnalyzerAgent::fake(fn (): array => perfectAnalysisPayload());

    $this->artisan('benchmark:run', ['--repeats' => 2, '--force' => true])
        ->expectsOutputToContain('gemini-3.5-flash/p3')
        ->expectsOutputToContain('Raw model')
        ->expectsOutputToContain('With reference lookup')
        ->expectsOutputToContain('1 / 2 / 0')
        ->expectsOutputToContain('1.00 / 1.00 (1 meals)')
        ->expectsOutputToContain('Saved as benchmark run #1.')
        ->assertSuccessful();

    $run = BenchmarkRun::query()->sole();

    expect($run->analyzer_version)->toBe('gemini-3.5-flash/p3')
        ->and($run->smoke)->toBeFalse()
        ->and($run->repeats)->toBe(2)
        ->and($run->meal_count)->toBe(1)
        ->and($run->toHarnessReport()->paths)->toHaveCount(2)
        ->and($run->toHarnessReport()->paths[0]->metrics->carbs->mae)->toBe(0.0);
});

it('reports deltas against the previous comparable run', function (): void {
    BenchmarkRun::factory()->create([
        'analyzer_version' => 'gemini-3-flash-preview/p2',
        'meal_count' => 1,
        'report' => reportWithCarbError(5.0),
    ]);

    seedGoldenPlate();
    FoodPhotoAnalyzerAgent::fake(fn (): array => perfectAnalysisPayload());

    $this->artisan('benchmark:run', ['--repeats' => 2, '--force' => true])
        ->expectsOutputToContain('Versus run #1 (gemini-3-flash-preview/p2')
        ->expectsOutputToContain('-5.00')
        ->expectsOutputToContain('Saved as benchmark run #2.')
        ->assertSuccessful();

    expect(BenchmarkRun::query()->count())->toBe(2);
});

it('does not compare full runs against smoke runs', function (): void {
    BenchmarkRun::factory()->create(['smoke' => true, 'report' => reportWithCarbError(5.0)]);

    seedGoldenPlate();
    FoodPhotoAnalyzerAgent::fake(fn (): array => perfectAnalysisPayload());

    $this->artisan('benchmark:run', ['--repeats' => 2, '--force' => true])
        ->doesntExpectOutputToContain('Versus run #')
        ->assertSuccessful();
});

it('aborts without spending when the cost confirmation is declined', function (): void {
    seedGoldenPlate();
    FoodPhotoAnalyzerAgent::fake(fn (): array => perfectAnalysisPayload());

    $this->artisan('benchmark:run', ['--repeats' => 2])
        ->expectsConfirmation('Run 4 analyses (~$0.02)?', 'no')
        ->assertSuccessful();

    FoodPhotoAnalyzerAgent::assertNeverPrompted();

    expect(BenchmarkRun::query()->count())->toBe(0);
});

it('fails fast when no benchmark meals exist', function (): void {
    $this->artisan('benchmark:run', ['--force' => true])
        ->expectsOutputToContain('No benchmark meals collected yet')
        ->assertFailed();
});
