<?php

declare(strict_types=1);

namespace App\Services\FoodDataProviders;

use App\DataObjects\NutritionData;
use App\Services\Contracts\FoodDataProviderInterface;
use App\Services\OpenFoodFactsService;

final readonly class OpenFoodFactsProvider implements FoodDataProviderInterface
{
    public function __construct(
        private OpenFoodFactsService $openFoodFacts,
    ) {}

    public function search(string $query, int $limit = 5): ?array
    {
        $cleanName = $this->cleanIngredientName($query);

        $searchResults = $this->openFoodFacts->searchProduct($cleanName, $limit);

        if (! $searchResults instanceof \App\DataObjects\OpenFoodFactsSearchResultData || $searchResults->isEmpty()) {
            return null;
        }

        $products = [];
        foreach ($searchResults->products as $product) {
            $nutrition = $this->openFoodFacts->extractNutritionPer100g($product);

            $products[] = [
                'id' => $product->code ?? '',
                'name' => $product->productName ?? 'Unknown',
                'brand' => $product->brands,
                'nutrition_per_100g' => $nutrition,
            ];
        }

        return $products !== [] ? $products : null;
    }

    public function getNutritionData(string $itemId): ?NutritionData
    {
        $product = $this->openFoodFacts->getProductByBarcode($itemId);

        if (! $product instanceof \App\DataObjects\OpenFoodFactsProductData) {
            return null; // @codeCoverageIgnore
        }

        return $this->openFoodFacts->extractNutritionPer100g($product);
    }

    public function cleanIngredientName(string $name): string
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
