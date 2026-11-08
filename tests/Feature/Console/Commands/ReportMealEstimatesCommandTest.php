<?php

declare(strict_types=1);

use App\Actions\BuildMealEstimateReport;
use App\Console\Commands\ReportMealEstimatesCommand;
use App\Models\MealEstimate;

covers(ReportMealEstimatesCommand::class, BuildMealEstimateReport::class);

it('reports corrections per model and the foods people renamed or added, hiding groups under 10 meals', function (): void {
    $this->artisan('meal-estimates:report')->expectsOutputToContain('Not enough meals yet')->assertSuccessful();

    MealEstimate::factory()->count(2)->create(['model_version' => 'model-a/p7']);
    MealEstimate::factory()->count(10)->corrected([
        ['name' => 'Fried rice', 'portion' => '1 cup (160g)', 'grams' => 160, 'calories' => 250, 'protein' => 4, 'carbs' => 45, 'fat' => 2],
        ['name' => 'Kimchi', 'portion' => '1 side', 'grams' => 50, 'calories' => 12, 'protein' => 1, 'carbs' => 2, 'fat' => 0],
    ])->create(['model_version' => 'model-a/p7']);
    MealEstimate::factory()->count(3)->create(['model_version' => 'model-b/p7']);

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
