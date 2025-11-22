<?php

declare(strict_types=1);

namespace App\Services\FoodDataProviders;

use App\Enums\IngredientSpecificity;
use App\Services\Contracts\FoodDataProviderInterface;

final readonly class FoodDataProviderResolver implements FoodDataProviderInterface
{
    public function __construct(
        private UsdaFoodDataProvider $usdaProvider,
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
        // Always use USDA for all ingredient types
        return $this->usdaProvider->search($ingredientName);
    }

    public function getNutritionData(string $productId): ?array
    {
        return $this->usdaProvider->getNutritionData($productId);
    }

    public function cleanIngredientName(string $name): string
    {
        return $this->usdaProvider->cleanIngredientName($name);
    }
}
