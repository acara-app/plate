<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Enums\IngredientSpecificity;

interface FoodDataProviderInterface
{
    /**
     * @return array<int, array{id: string, name: string, brand: string|null, calories: float|null, protein: float|null, carbs: float|null, fat: float|null, fiber: float|null, sugar: float|null, sodium: float|null, source: string}>
     */
    public function search(string $ingredientName): array;

    /**
     * @return array<int, array{id: string, name: string, brand: string|null, calories: float|null, protein: float|null, carbs: float|null, fat: float|null, fiber: float|null, sugar: float|null, sodium: float|null, source: string}>
     */
    public function searchWithSpecificity(string $ingredientName, IngredientSpecificity $specificity, ?string $barcode = null): array;

    /**
     * @return array{id: string, name: string, brand: string|null, calories: float|null, protein: float|null, carbs: float|null, fat: float|null, fiber: float|null, sugar: float|null, sodium: float|null, source: string}|null
     */
    public function getNutritionData(string $productId): ?array;

    public function cleanIngredientName(string $name): string;
}
