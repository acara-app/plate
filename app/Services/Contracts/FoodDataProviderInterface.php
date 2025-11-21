<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\DataObjects\NutritionData;

interface FoodDataProviderInterface
{
    /**
     * Search for food items by query string
     *
     * @param  string  $query  The search query
     * @param  int  $limit  Maximum number of results to return
     * @return array<int, array{id: string, name: string, brand: ?string, nutrition_per_100g: ?NutritionData}>|null
     */
    public function search(string $query, int $limit = 5): ?array;

    /**
     * Get detailed nutrition information for a specific food item
     *
     * @param  string  $itemId  The unique identifier for the food item
     */
    public function getNutritionData(string $itemId): ?NutritionData;

    /**
     * Clean and normalize ingredient name for better search results
     */
    public function cleanIngredientName(string $name): string;
}
