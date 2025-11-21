<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataObjects\MealData;

final readonly class CorrectMealNutrition
{
    /**
     * @param  array{verified_ingredients: array<int, array{name: string, quantity: string|null, nutrition_per_100g: array{calories: float|null, protein: float|null, carbs: float|null, fat: float|null, fiber: float|null, sugar: float|null, sodium: float|null}|null, matched: bool}>, total_verified: null, verification_success: bool, verification_rate: float}  $verificationData
     */
    public function handle(MealData $mealData, array $verificationData): MealData
    {
        if (! $verificationData['verification_success'] || $verificationData['verification_rate'] < 0.3) {

            return new MealData(
                dayNumber: $mealData->dayNumber,
                type: $mealData->type,
                name: $mealData->name,
                description: $mealData->description,
                preparationInstructions: $mealData->preparationInstructions,
                ingredients: $mealData->ingredients,
                portionSize: $mealData->portionSize,
                calories: $mealData->calories,
                proteinGrams: $mealData->proteinGrams,
                carbsGrams: $mealData->carbsGrams,
                fatGrams: $mealData->fatGrams,
                preparationTimeMinutes: $mealData->preparationTimeMinutes,
                sortOrder: $mealData->sortOrder,
                metadata: $mealData->metadata,
                verificationMetadata: [
                    'verified' => false,
                    'verification_rate' => $verificationData['verification_rate'],
                    'confidence' => 'low',
                    'source' => 'ai_estimate',
                    'verified_ingredients' => $verificationData['verified_ingredients'],
                ],
            );
        }

        $verifiedNutrition = $this->calculateAverageNutrition($verificationData['verified_ingredients']);

        if ($verifiedNutrition === null) {
            return new MealData(
                dayNumber: $mealData->dayNumber,
                type: $mealData->type,
                name: $mealData->name,
                description: $mealData->description,
                preparationInstructions: $mealData->preparationInstructions,
                ingredients: $mealData->ingredients,
                portionSize: $mealData->portionSize,
                calories: $mealData->calories,
                proteinGrams: $mealData->proteinGrams,
                carbsGrams: $mealData->carbsGrams,
                fatGrams: $mealData->fatGrams,
                preparationTimeMinutes: $mealData->preparationTimeMinutes,
                sortOrder: $mealData->sortOrder,
                metadata: $mealData->metadata,
                verificationMetadata: [
                    'verified' => false,
                    'verification_rate' => $verificationData['verification_rate'],
                    'confidence' => 'medium',
                    'source' => 'ai_estimate',
                    'note' => 'Ingredients matched but nutrition data incomplete',
                    'verified_ingredients' => $verificationData['verified_ingredients'],
                ],
            );
        }

        $correctedData = $this->applyCorrectionStrategy(
            [
                'calories' => $mealData->calories,
                'protein' => $mealData->proteinGrams,
                'carbs' => $mealData->carbsGrams,
                'fat' => $mealData->fatGrams,
            ],
            $verifiedNutrition
        );

        return new MealData(
            dayNumber: $mealData->dayNumber,
            type: $mealData->type,
            name: $mealData->name,
            description: $mealData->description,
            preparationInstructions: $mealData->preparationInstructions,
            ingredients: $mealData->ingredients,
            portionSize: $mealData->portionSize,
            calories: $correctedData['calories'],
            proteinGrams: $correctedData['protein'],
            carbsGrams: $correctedData['carbs'],
            fatGrams: $correctedData['fat'],
            preparationTimeMinutes: $mealData->preparationTimeMinutes,
            sortOrder: $mealData->sortOrder,
            metadata: $mealData->metadata,
            verificationMetadata: [
                'verified' => true,
                'verification_rate' => $verificationData['verification_rate'],
                'confidence' => 'high',
                'source' => 'openfoodfacts_verified',
                'original_ai_values' => [
                    'calories' => $mealData->calories,
                    'protein' => $mealData->proteinGrams,
                    'carbs' => $mealData->carbsGrams,
                    'fat' => $mealData->fatGrams,
                ],
                'verified_values' => $verifiedNutrition,
                'corrections_applied' => $correctedData['corrections_applied'],
                'verified_ingredients' => $verificationData['verified_ingredients'],
            ],
        );
    }

    /**
     * @param  array<int, array{name: string, quantity: string|null, nutrition_per_100g: array{calories: float|null, protein: float|null, carbs: float|null, fat: float|null, fiber: float|null, sugar: float|null, sodium: float|null}|null, matched: bool}>  $verifiedIngredients
     * @return array{calories: float, protein: float, carbs: float, fat: float}|null
     */
    private function calculateAverageNutrition(array $verifiedIngredients): ?array
    {
        $matchedIngredients = array_filter($verifiedIngredients, fn (array $ing): bool => $ing['matched'] && $ing['nutrition_per_100g'] !== null);

        if ($matchedIngredients === []) {
            return null;
        }

        $totals = ['calories' => 0.0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0];
        $count = 0;

        foreach ($matchedIngredients as $ingredient) {
            $nutrition = $ingredient['nutrition_per_100g'];

            $totals['calories'] += $nutrition['calories'] ?? 0.0;
            $totals['protein'] += $nutrition['protein'] ?? 0.0;
            $totals['carbs'] += $nutrition['carbs'] ?? 0.0;
            $totals['fat'] += $nutrition['fat'] ?? 0.0;
            $count++;
        }

        return [
            'calories' => $totals['calories'] / $count,
            'protein' => $totals['protein'] / $count,
            'carbs' => $totals['carbs'] / $count,
            'fat' => $totals['fat'] / $count,
        ];
    }

    /**
     * @param  array{calories: float, protein: float|null, carbs: float|null, fat: float|null}  $aiEstimate
     * @param  array{calories: float, protein: float, carbs: float, fat: float}  $verifiedData
     * @return array{calories: float, protein: float, carbs: float, fat: float, corrections_applied: array<string, array{original: float, verified: float, corrected: float, discrepancy_percent: float}>}
     */
    private function applyCorrectionStrategy(array $aiEstimate, array $verifiedData): array
    {
        $corrected = [];
        $corrections = [];
        $discrepancyThreshold = 15.0;

        foreach (['calories', 'protein', 'carbs', 'fat'] as $nutrient) {
            $ai = $aiEstimate[$nutrient] ?? 0.0;
            $verified = $verifiedData[$nutrient];

            if ($ai <= 0) {
                $corrected[$nutrient] = $verified;
                $corrections[$nutrient] = [
                    'original' => $ai,
                    'verified' => $verified,
                    'corrected' => $verified,
                    'discrepancy_percent' => 100.0,
                ];

                continue;
            }

            $discrepancy = abs($ai - $verified) / $ai * 100;

            if ($discrepancy > $discrepancyThreshold) {
                $corrected[$nutrient] = round($ai * 0.7 + $verified * 0.3, 2);
                $corrections[$nutrient] = [
                    'original' => $ai,
                    'verified' => $verified,
                    'corrected' => $corrected[$nutrient],
                    'discrepancy_percent' => round($discrepancy, 2),
                ];
            } else {
                $corrected[$nutrient] = $ai;
            }
        }

        return [
            'calories' => $corrected['calories'],
            'protein' => $corrected['protein'],
            'carbs' => $corrected['carbs'],
            'fat' => $corrected['fat'],
            'corrections_applied' => $corrections,
        ];
    }
}
