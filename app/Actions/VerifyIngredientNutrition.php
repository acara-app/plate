<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataObjects\NutritionData;
use App\Services\OpenFoodFactsService;

final readonly class VerifyIngredientNutrition
{
    public function __construct(
        private OpenFoodFactsService $openFoodFacts,
    ) {}

    /**
     * Verify ingredients from structured array
     *
     * @param  array<int, array{name: string, quantity: string}>  $ingredients
     * @return array{verified_ingredients: array<int, array{name: string, quantity: string, nutrition_per_100g: array{calories: float|null, protein: float|null, carbs: float|null, fat: float|null, fiber: float|null, sugar: float|null, sodium: float|null}|null, matched: bool}>, total_verified: null, verification_success: bool, verification_rate: float}
     */
    public function handle(array $ingredients): array
    {
        $verifiedIngredients = [];
        $successCount = 0;

        foreach ($ingredients as $ingredient) {
            $verifiedData = $this->verifyIngredient($ingredient['name']);

            $verifiedIngredients[] = [
                'name' => $ingredient['name'],
                'quantity' => $ingredient['quantity'],
                'nutrition_per_100g' => $verifiedData?->toArray(),
                'matched' => $verifiedData instanceof NutritionData,
            ];

            if ($verifiedData instanceof NutritionData) {
                $successCount++;
            }
        }

        $verificationRate = count($ingredients) > 0 ? $successCount / count($ingredients) : 0.0;

        return [
            'verified_ingredients' => $verifiedIngredients,
            'total_verified' => null,
            'verification_success' => $verificationRate > 0.5,
            'verification_rate' => $verificationRate,
        ];
    }

    private function verifyIngredient(string $ingredientName): ?NutritionData
    {
        $cleanName = $this->cleanIngredientName($ingredientName);

        $searchResults = $this->openFoodFacts->searchProduct($cleanName, 3);

        if (! $searchResults instanceof \App\DataObjects\OpenFoodFactsSearchResultData || $searchResults->isEmpty()) {
            return null;
        }

        $bestMatch = $searchResults->getBestMatch();

        if (! $bestMatch instanceof \App\DataObjects\OpenFoodFactsProductData) {
            return null; // @codeCoverageIgnore
        }

        return $this->openFoodFacts->extractNutritionPer100g($bestMatch);
    }

    private function cleanIngredientName(string $name): string
    {
        // Remove common cooking descriptors
        $cleanName = preg_replace('/\b(fresh|organic|raw|cooked|grilled|baked|steamed|chopped|diced|sliced)\b/i', '', $name);
        // Remove parentheses and their content
        $cleanName = preg_replace('/\([^)]*\)/', '', $cleanName ?? $name);
        // Normalize whitespace
        $cleanName = preg_replace('/\s+/', ' ', $cleanName ?? $name);

        return mb_trim($cleanName ?? $name);
    }
}
