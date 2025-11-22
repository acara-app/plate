<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataObjects\CorrectedNutritionData;
use App\DataObjects\IngredientVerificationResultData;
use App\DataObjects\MealData;
use App\DataObjects\NutritionCorrectionData;
use App\DataObjects\NutritionData;
use App\DataObjects\NutritionWithSourceData;

final readonly class CorrectMealNutrition
{
    public function handle(MealData $mealData, IngredientVerificationResultData $verificationData): MealData
    {
        $source = $verificationData->source;

        if (! $verificationData->verificationSuccess || $verificationData->verificationRate < 0.3) {

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
                    'verification_rate' => $verificationData->verificationRate,
                    'confidence' => 'low',
                    'source' => 'ai_estimate',
                    'verified_ingredients' => $verificationData->verifiedIngredients->toArray(),
                ],
            );
        }

        $verifiedNutrition = $this->calculateAverageNutrition($verificationData);

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
                    'verification_rate' => $verificationData->verificationRate,
                    'confidence' => 'medium',
                    'source' => 'ai_estimate',
                    'note' => 'Ingredients matched but nutrition data incomplete',
                    'verified_ingredients' => $verificationData->verifiedIngredients->toArray(),
                ],
            );
        }

        $aiEstimate = new NutritionData(
            calories: $mealData->calories,
            protein: $mealData->proteinGrams,
            carbs: $mealData->carbsGrams,
            fat: $mealData->fatGrams,
            fiber: null,
            sugar: null,
            sodium: null,
        );

        $verifiedData = new NutritionData(
            calories: $verifiedNutrition['calories'],
            protein: $verifiedNutrition['protein'],
            carbs: $verifiedNutrition['carbs'],
            fat: $verifiedNutrition['fat'],
            fiber: null,
            sugar: null,
            sodium: null,
        );

        $correctedData = $this->applyCorrectionStrategy($aiEstimate, $verifiedData);

        return new MealData(
            dayNumber: $mealData->dayNumber,
            type: $mealData->type,
            name: $mealData->name,
            description: $mealData->description,
            preparationInstructions: $mealData->preparationInstructions,
            ingredients: $mealData->ingredients,
            portionSize: $mealData->portionSize,
            calories: $correctedData->calories,
            proteinGrams: $correctedData->protein,
            carbsGrams: $correctedData->carbs,
            fatGrams: $correctedData->fat,
            preparationTimeMinutes: $mealData->preparationTimeMinutes,
            sortOrder: $mealData->sortOrder,
            metadata: $mealData->metadata,
            verificationMetadata: [
                'verified' => true,
                'verification_rate' => $verificationData->verificationRate,
                'confidence' => 'high',
                'source' => $source.'_verified',
                'original_ai_values' => [
                    'calories' => $mealData->calories,
                    'protein' => $mealData->proteinGrams,
                    'carbs' => $mealData->carbsGrams,
                    'fat' => $mealData->fatGrams,
                ],
                'verified_values' => $verifiedNutrition,
                'corrections_applied' => array_map(
                    fn (NutritionCorrectionData $correction): array => $correction->toArray(),
                    $correctedData->correctionsApplied
                ),
                'verified_ingredients' => $verificationData->verifiedIngredients->toArray(),
            ],
        );
    }

    /**
     * @return array{calories: float, protein: float, carbs: float, fat: float}|null
     */
    private function calculateAverageNutrition(IngredientVerificationResultData $verificationData): ?array
    {
        $totals = ['calories' => 0.0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0];
        $count = 0;

        foreach ($verificationData->verifiedIngredients as $ingredient) {
            if (! $ingredient->matched) {
                continue; // @codeCoverageIgnore
            }
            if (! $ingredient->nutritionPer100g instanceof NutritionWithSourceData) {
                continue; // @codeCoverageIgnore
            }
            $nutrition = $ingredient->nutritionPer100g;

            $totals['calories'] += $nutrition->calories ?? 0.0;
            $totals['protein'] += $nutrition->protein ?? 0.0;
            $totals['carbs'] += $nutrition->carbs ?? 0.0;
            $totals['fat'] += $nutrition->fat ?? 0.0;
            $count++;
        }

        if ($count === 0) {
            return null;
        }

        return [
            'calories' => $totals['calories'] / $count,
            'protein' => $totals['protein'] / $count,
            'carbs' => $totals['carbs'] / $count,
            'fat' => $totals['fat'] / $count,
        ];
    }

    private function applyCorrectionStrategy(NutritionData $aiEstimate, NutritionData $verifiedData): CorrectedNutritionData
    {
        $corrected = [];
        $corrections = [];
        $discrepancyThreshold = 15.0;

        foreach (['calories', 'protein', 'carbs', 'fat'] as $nutrient) {
            $ai = $aiEstimate->$nutrient ?? 0.0;
            $verified = $verifiedData->$nutrient ?? 0.0;

            if ($ai <= 0) {
                $corrected[$nutrient] = $verified;
                $corrections[$nutrient] = new NutritionCorrectionData(
                    original: $ai,
                    verified: $verified,
                    corrected: $verified,
                    discrepancyPercent: 100.0,
                );

                continue;
            }

            $discrepancy = abs($ai - $verified) / $ai * 100;

            if ($discrepancy > $discrepancyThreshold) {
                $corrected[$nutrient] = round($ai * 0.7 + $verified * 0.3, 2);
                $corrections[$nutrient] = new NutritionCorrectionData(
                    original: $ai,
                    verified: $verified,
                    corrected: $corrected[$nutrient],
                    discrepancyPercent: round($discrepancy, 2),
                );
            } else {
                $corrected[$nutrient] = $ai;
            }
        }

        return new CorrectedNutritionData(
            calories: $corrected['calories'],
            protein: $corrected['protein'],
            carbs: $corrected['carbs'],
            fat: $corrected['fat'],
            correctionsApplied: $corrections,
        );
    }
}
