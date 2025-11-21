<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataObjects\NutritionData;
use App\Services\Contracts\FoodDataProviderInterface;
use App\Services\FoodDataProviders\OpenFoodFactsProvider;
use Illuminate\Container\Attributes\Give;

final readonly class VerifyIngredientNutrition
{
    public function __construct(
        #[Give(OpenFoodFactsProvider::class)]
        private FoodDataProviderInterface $foodDataProvider,
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
        $searchResults = $this->foodDataProvider->search($ingredientName, 3);

        if ($searchResults === null || $searchResults === []) {
            return null;
        }

        // Get the best match (first result, as providers should return sorted by relevance)
        $bestMatch = $searchResults[0] ?? null;

        if ($bestMatch === null) {
            return null; // @codeCoverageIgnore
        }

        return $bestMatch['nutrition_per_100g'];
    }
}
