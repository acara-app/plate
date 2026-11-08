<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnalysisDraftSource;
use App\Models\MealEstimateDailyStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealEstimateDailyStat>
 */
final class MealEstimateDailyStatFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now()->subDay()->toDateString(),
            'model_version' => 'gemini-3.8-flash/p7',
            'source' => AnalysisDraftSource::AuthenticatedSnapToTrack,
            'meals' => 12,
            'corrected' => 3,
            'kcal_changes' => [52 => 2, -20 => 1],
            'kcal_change_percents' => [25 => 2, -10 => 1],
            'carbs_changes' => [2 => 2, -5 => 1],
        ];
    }
}
