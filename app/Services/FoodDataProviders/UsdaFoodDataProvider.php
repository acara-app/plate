<?php

declare(strict_types=1);

namespace App\Services\FoodDataProviders;

use App\Enums\IngredientSpecificity;
use App\Services\Contracts\FoodDataProviderInterface;
use App\Services\UsdaFoodDataService;

final readonly class UsdaFoodDataProvider implements FoodDataProviderInterface
{
    public function __construct(private UsdaFoodDataService $usdaService) {}

    public function search(string $ingredientName): array
    {
        $cleanedName = $this->cleanIngredientName($ingredientName);
        $searchResults = $this->usdaService->searchFoods($cleanedName, 5);

        if ($searchResults === null || $searchResults === []) {
            return [];
        }

        $results = [];
        foreach ($searchResults as $food) {
            if (empty($food['id'])) {
                continue;
            }

            $foodData = $this->usdaService->getFoodById($food['id']);
            if (! $foodData) {
                continue;
            }

            $nutrition = $this->usdaService->extractNutritionPer100g($foodData);
            if (! $nutrition instanceof \App\DataObjects\NutritionData) {
                continue;
            }

            $results[] = [
                'id' => $food['id'],
                'name' => $food['name'],
                'brand' => $food['brand'],
                'calories' => $nutrition->calories,
                'protein' => $nutrition->protein,
                'carbs' => $nutrition->carbs,
                'fat' => $nutrition->fat,
                'fiber' => $nutrition->fiber,
                'sugar' => $nutrition->sugar,
                'sodium' => $nutrition->sodium,
                'source' => 'usda',
            ];
        }

        return $results;
    }

    public function searchWithSpecificity(string $ingredientName, IngredientSpecificity $specificity, ?string $barcode = null): array
    {
        return $this->search($ingredientName);
    }

    public function getNutritionData(string $productId): ?array
    {
        $foodData = $this->usdaService->getFoodById($productId);
        if (! $foodData) {
            return null;
        }

        $nutrition = $this->usdaService->extractNutritionPer100g($foodData);
        if (! $nutrition instanceof \App\DataObjects\NutritionData) {
            return null;
        }

        return [
            'id' => $productId,
            'name' => is_string($foodData['description'] ?? null) ? $foodData['description'] : '',
            'brand' => is_string($foodData['brandOwner'] ?? null) ? $foodData['brandOwner'] : null,
            'calories' => $nutrition->calories,
            'protein' => $nutrition->protein,
            'carbs' => $nutrition->carbs,
            'fat' => $nutrition->fat,
            'fiber' => $nutrition->fiber,
            'sugar' => $nutrition->sugar,
            'sodium' => $nutrition->sodium,
            'source' => 'usda',
        ];
    }

    public function cleanIngredientName(string $name): string
    {
        return $this->usdaService->cleanIngredientName($name);
    }
}
