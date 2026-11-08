<?php

declare(strict_types=1);

use App\Actions\AggregateDailyMealEstimates;
use App\Actions\BuildMealEstimateReport;
use App\Console\Commands\AggregateDailyMealEstimatesCommand;
use App\Console\Commands\ReportMealEstimatesCommand;
use App\Models\MealEstimate;
use App\Models\MealEstimateDailyFoodChange;
use App\Models\MealEstimateDailyStat;
use App\Utilities\Histogram;

covers(
    ReportMealEstimatesCommand::class,
    BuildMealEstimateReport::class,
    AggregateDailyMealEstimatesCommand::class,
    AggregateDailyMealEstimates::class,
    Histogram::class,
    MealEstimateDailyStat::class,
    MealEstimateDailyFoodChange::class,
);

it('reports corrections per model and the foods people renamed or added, hiding groups under 10 meals', function (): void {
    $this->artisan('meal-estimates:report')->expectsOutputToContain('Not enough meals yet')->assertSuccessful();

    $yesterday = now()->subDay();
    MealEstimate::factory()->count(2)->create(['model_version' => 'model-a/p7', 'created_at' => $yesterday]);
    MealEstimate::factory()->count(10)->corrected([
        ['name' => 'Fried rice', 'portion' => '1 cup (160g)', 'grams' => 160, 'calories' => 250, 'protein' => 4, 'carbs' => 45, 'fat' => 2],
        ['name' => 'Kimchi', 'portion' => '1 side', 'grams' => 50, 'calories' => 12, 'protein' => 1, 'carbs' => 2, 'fat' => 0],
    ])->create(['model_version' => 'model-a/p7', 'created_at' => $yesterday]);
    MealEstimate::factory()->count(3)->create(['model_version' => 'model-b/p7', 'created_at' => $yesterday]);

    $this->artisan('meal-estimates:aggregate-daily')->assertSuccessful();

    $this->artisan('meal-estimates:report')
        ->expectsOutputToContain('15 meals, 10 corrected (67%)')
        ->expectsTable(
            ['Model', 'Source', 'Meals', 'Corrected', 'kcal change when corrected (median)', 'Carbs change when corrected (median)'],
            [['model-a/p7', 'authenticated_snap_to_track', 12, '10 (83%)', '+52 (+25%)', '+2.0 g']],
        )
        ->expectsTable(['AI food', 'Changed to', 'Meals'], [['white rice cooked', 'fried rice', 10]])
        ->expectsTable(['Change', 'Food', 'Meals'], [['added', 'kimchi', 10]])
        ->expectsOutputToContain('1 model group with fewer than 10 meals is hidden.')
        ->assertSuccessful();
});

it('combines stored days into one true median, so re-aggregating a day never counts it twice', function (): void {
    $rice = fn (int $calories): array => [['name' => 'Steamed rice', 'portion' => '1 cup (160g)', 'grams' => 160, 'calories' => $calories, 'protein' => 4, 'carbs' => 45, 'fat' => 0.5]];
    MealEstimate::factory()->count(5)->corrected($rice(310))->create(['model_version' => 'model-a/p7', 'created_at' => now()->subDays(2)]);
    MealEstimate::factory()->count(6)->corrected($rice(220))->create(['model_version' => 'model-a/p7', 'created_at' => now()->subDay()]);

    $this->artisan('meal-estimates:aggregate-daily', ['--to' => now()->subDays(2)->toDateString(), '--days' => 1])->assertSuccessful();
    $this->artisan('meal-estimates:aggregate-daily')->assertSuccessful();

    $this->artisan('meal-estimates:report')
        ->expectsOutputToContain('11 meals, 11 corrected (100%)')
        ->expectsTable(
            ['Model', 'Source', 'Meals', 'Corrected', 'kcal change when corrected (median)', 'Carbs change when corrected (median)'],
            [['model-a/p7', 'authenticated_snap_to_track', 11, '11 (100%)', '+10 (+5%)', '+0.0 g']],
        )
        ->assertSuccessful();
});

it('forgets stored food names 12 months after the day they were saved', function (): void {
    MealEstimateDailyFoodChange::factory()->create(['date' => now()->subMonths(MealEstimate::RETENTION_MONTHS)->subDay()->toDateString()]);
    $recent = MealEstimateDailyFoodChange::factory()->create();

    $this->artisan('model:prune', ['--model' => [MealEstimateDailyFoodChange::class]])->assertSuccessful();

    expect(MealEstimateDailyFoodChange::query()->pluck('id')->all())->toBe([$recent->id]);
});
