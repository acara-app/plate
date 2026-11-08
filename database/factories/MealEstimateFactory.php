<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnalysisDraftSource;
use App\Enums\EstimateValidationStatus;
use App\Models\MealEstimate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MealEstimate>
 */
final class MealEstimateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'health_group_id' => Str::uuid()->toString(),
            'source' => AnalysisDraftSource::AuthenticatedSnapToTrack,
            'model_version' => 'gemini-3.8-flash/p7',
            'original_prediction' => [
                'items' => [
                    [
                        'name' => 'Steamed rice',
                        'calories' => 210.0,
                        'protein' => 4.0,
                        'carbs' => 45.0,
                        'fat' => 0.5,
                        'portion' => '1 cup (160g)',
                        'grams' => 160.0,
                        'match_name' => 'white rice cooked',
                        'provenance' => 'model',
                    ],
                ],
                'total_calories' => 210.0,
                'total_protein' => 4.0,
                'total_carbs' => 45.0,
                'total_fat' => 0.5,
                'confidence' => 85,
            ],
            'corrected_value' => null,
            'validation_status' => EstimateValidationStatus::AiGenerated,
            'corrected_at' => null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function corrected(array $items): self
    {
        $total = fn (string $nutrient): float => round(array_sum(array_map(
            fn (array $item): float => is_numeric($item[$nutrient] ?? null) ? (float) $item[$nutrient] : 0.0,
            $items,
        )), 1);

        return $this->state(fn (): array => [
            'corrected_value' => [
                'items' => $items,
                'totals' => [
                    'calories' => $total('calories'),
                    'protein' => $total('protein'),
                    'carbs' => $total('carbs'),
                    'fat' => $total('fat'),
                    'fiber' => null,
                ],
            ],
            'validation_status' => EstimateValidationStatus::UserCorrected,
            'corrected_at' => now(),
        ]);
    }
}
