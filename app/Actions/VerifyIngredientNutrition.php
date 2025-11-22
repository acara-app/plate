<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\IngredientSpecificity;
use App\Services\Contracts\FoodDataProviderInterface;
use App\Services\FoodDataProviders\FoodDataProviderResolver;
use Illuminate\Container\Attributes\Give;

final readonly class VerifyIngredientNutrition
{
    public function __construct(
        #[Give(FoodDataProviderResolver::class)]
        private FoodDataProviderInterface $foodDataProvider,
    ) {}

    /**
     * @param  array<int, array{name: string, quantity: string, specificity?: string, barcode?: string}>  $ingredients
     * @return array{verified_ingredients: array<int, array{name: string, quantity: string, specificity: string, nutrition_per_100g: array{calories: float|null, protein: float|null, carbs: float|null, fat: float|null, fiber: float|null, sugar: float|null, sodium: float|null, source: string}|null, matched: bool}>, total_verified: int, verification_success: bool, verification_rate: float, verified: bool, source: string}
     */
    public function handle(array $ingredients): array
    {
        $verifiedIngredients = [];
        $successCount = 0;

        foreach ($ingredients as $ingredient) {
            $specificityValue = $ingredient['specificity'] ?? 'generic';
            $specificity = IngredientSpecificity::tryFrom($specificityValue) ?? IngredientSpecificity::Generic;
            $barcode = $ingredient['barcode'] ?? null;

            $verifiedData = $this->verifyIngredient(
                $ingredient['name'],
                $specificity,
                $barcode
            );

            $verifiedIngredients[] = [
                'name' => $ingredient['name'],
                'quantity' => $ingredient['quantity'],
                'specificity' => $specificity->value,
                'nutrition_per_100g' => $verifiedData,
                'matched' => $verifiedData !== null,
            ];

            if ($verifiedData !== null) {
                $successCount++;
            }
        }

        $verificationRate = count($ingredients) > 0 ? $successCount / count($ingredients) : 0.0;
        $verified = $verificationRate > 0.5;

        /** @var array<int, array{name: string, quantity: string, specificity: string, nutrition_per_100g: array{calories: float|null, protein: float|null, carbs: float|null, fat: float|null, fiber: float|null, sugar: float|null, sodium: float|null, source: string}|null, matched: bool}> $verifiedIngredients */
        $matchedIngredients = array_filter($verifiedIngredients, fn (array $i): bool => $i['matched']);
        $sources = array_column($matchedIngredients, 'nutrition_per_100g');
        $sourcesFlattened = array_column($sources, 'source');
        /** @var array<string, int<1, max>> $sourceCount */
        $sourceCount = array_count_values($sourcesFlattened);
        arsort($sourceCount);
        $primarySource = array_key_first($sourceCount) ?? 'mixed';

        return [
            'verified_ingredients' => $verifiedIngredients,
            'total_verified' => $successCount,
            'verification_success' => $verified,
            'verification_rate' => $verificationRate,
            'verified' => $verified,
            'source' => $primarySource,
        ];
    }

    /**
     * @return array{calories: float|null, protein: float|null, carbs: float|null, fat: float|null, fiber: float|null, sugar: float|null, sodium: float|null, source: string}|null
     */
    private function verifyIngredient(string $ingredientName, IngredientSpecificity $specificity, ?string $barcode): ?array
    {
        $searchResults = $this->foodDataProvider->searchWithSpecificity($ingredientName, $specificity, $barcode);

        if ($searchResults === []) {
            return null;
        }

        $bestMatch = $searchResults[0] ?? null;

        if ($bestMatch === null) {
            return null; // @codeCoverageIgnore
        }

        return [
            'calories' => $bestMatch['calories'],
            'protein' => $bestMatch['protein'],
            'carbs' => $bestMatch['carbs'],
            'fat' => $bestMatch['fat'],
            'fiber' => $bestMatch['fiber'],
            'sugar' => $bestMatch['sugar'],
            'sodium' => $bestMatch['sodium'],
            'source' => $bestMatch['source'],
        ];
    }
}
