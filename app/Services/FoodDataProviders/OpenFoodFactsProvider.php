<?php

declare(strict_types=1);

namespace App\Services\FoodDataProviders;

use App\Enums\IngredientSpecificity;
use App\Services\Contracts\FoodDataProviderInterface;
use App\Services\OpenFoodFactsService;

final readonly class OpenFoodFactsProvider implements FoodDataProviderInterface
{
    public function __construct(
        private OpenFoodFactsService $openFoodFacts,
    ) {}

    public function search(string $ingredientName): array
    {
        $cleanName = $this->cleanIngredientName($ingredientName);

        $searchResults = $this->openFoodFacts->searchProduct($cleanName, 5);

        if (! $searchResults instanceof \App\DataObjects\OpenFoodFactsSearchResultData || $searchResults->isEmpty()) {
            return [];
        }

        $products = [];
        foreach ($searchResults->products as $product) {
            /** @var \App\DataObjects\OpenFoodFactsProductData $product */
            $nutrition = $this->openFoodFacts->extractNutritionPer100g($product);

            if (! $nutrition instanceof \App\DataObjects\NutritionData) {
                continue;
            }

            $products[] = [
                'id' => $product->code ?? '',
                'name' => $product->productName ?? 'Unknown',
                'brand' => $product->brands,
                'calories' => $nutrition->calories,
                'protein' => $nutrition->protein,
                'carbs' => $nutrition->carbs,
                'fat' => $nutrition->fat,
                'fiber' => $nutrition->fiber,
                'sugar' => $nutrition->sugar,
                'sodium' => $nutrition->sodium,
                'source' => 'openfoodfacts',
            ];
        }

        return $products;
    }

    public function searchWithSpecificity(string $ingredientName, IngredientSpecificity $specificity, ?string $barcode = null): array
    {
        return $this->search($ingredientName);
    }

    public function getNutritionData(string $productId): ?array
    {
        $product = $this->openFoodFacts->getProductByBarcode($productId);

        if (! $product instanceof \App\DataObjects\OpenFoodFactsProductData) {
            return null; // @codeCoverageIgnore
        }

        $nutrition = $this->openFoodFacts->extractNutritionPer100g($product);

        if (! $nutrition instanceof \App\DataObjects\NutritionData) {
            return null;
        }

        return [
            'id' => $product->code ?? '',
            'name' => $product->productName ?? 'Unknown',
            'brand' => $product->brands,
            'calories' => $nutrition->calories,
            'protein' => $nutrition->protein,
            'carbs' => $nutrition->carbs,
            'fat' => $nutrition->fat,
            'fiber' => $nutrition->fiber,
            'sugar' => $nutrition->sugar,
            'sodium' => $nutrition->sodium,
            'source' => 'openfoodfacts',
        ];
    }

    public function cleanIngredientName(string $name): string
    {
        $cleanName = preg_replace('/\b(fresh|organic|raw|cooked|grilled|baked|steamed|chopped|diced|sliced)\b/i', '', $name);
        $cleanName = preg_replace('/\([^)]*\)/', '', $cleanName ?? $name);
        $cleanName = preg_replace('/\s+/', ' ', $cleanName ?? $name);

        return mb_trim($cleanName ?? $name);
    }
}
