<?php

declare(strict_types=1);

namespace App\Services\FoodDataProviders;

use App\Enums\IngredientSpecificity;
use App\Services\Contracts\FoodDataProviderInterface;

final readonly class FoodDataProviderResolver implements FoodDataProviderInterface
{
    public function __construct(
        private UsdaFoodDataProvider $usdaProvider,
        private OpenFoodFactsProvider $offProvider,
    ) {}

    public function search(string $ingredientName): array
    {
        return $this->usdaProvider->search($ingredientName);
    }

    /**
     * @return array<int, array{id: string, name: string, brand: string|null, calories: float|null, protein: float|null, carbs: float|null, fat: float|null, fiber: float|null, sugar: float|null, sodium: float|null, source: string}>
     */
    public function searchWithSpecificity(string $ingredientName, IngredientSpecificity $specificity, ?string $barcode = null): array
    {
        if ($barcode && $specificity === IngredientSpecificity::Specific) {
            $result = $this->offProvider->getNutritionData($barcode);
            if ($result) {
                return [$result];
            }
        }

        if ($specificity === IngredientSpecificity::Generic) {
            return $this->usdaProvider->search($ingredientName);
        }

        return $this->offProvider->search($ingredientName);
    }

    public function getNutritionData(string $productId): ?array
    {
        if (is_numeric($productId)) {
            $result = $this->usdaProvider->getNutritionData($productId);
            if ($result) {
                return $result;
            }
        }

        return $this->offProvider->getNutritionData($productId);
    }

    public function cleanIngredientName(string $name): string
    {
        return $this->usdaProvider->cleanIngredientName($name);
    }
}
