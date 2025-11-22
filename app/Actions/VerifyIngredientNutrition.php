<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataObjects\IngredientData;
use App\DataObjects\IngredientVerificationResultData;
use App\DataObjects\NutritionWithSourceData;
use App\DataObjects\VerifiedIngredientData;
use App\Enums\IngredientSpecificity;
use App\Services\Contracts\FoodDataProviderInterface;
use App\Services\FoodDataProviders\FoodDataProviderResolver;
use Illuminate\Container\Attributes\Give;
use Spatie\LaravelData\DataCollection;

final readonly class VerifyIngredientNutrition
{
    public function __construct(
        #[Give(FoodDataProviderResolver::class)]
        private FoodDataProviderInterface $foodDataProvider,
    ) {}

    /**
     * @param  DataCollection<int, IngredientData>  $ingredients
     */
    public function handle(DataCollection $ingredients): IngredientVerificationResultData
    {
        $verifiedIngredients = [];
        $successCount = 0;

        foreach ($ingredients as $ingredient) {
            $specificityValue = $ingredient->specificity ?? 'generic';
            $specificity = IngredientSpecificity::tryFrom($specificityValue) ?? IngredientSpecificity::Generic;
            $barcode = $ingredient->barcode ?? null;

            $verifiedData = $this->verifyIngredient(
                $ingredient->name,
                $specificity,
                $barcode
            );

            $verifiedIngredients[] = new VerifiedIngredientData(
                name: $ingredient->name,
                quantity: $ingredient->quantity,
                specificity: $specificity->value,
                nutritionPer100g: $verifiedData,
                matched: $verifiedData instanceof NutritionWithSourceData,
            );

            if ($verifiedData instanceof NutritionWithSourceData) {
                $successCount++;
            }
        }

        $totalIngredients = count($verifiedIngredients);
        $verificationRate = $totalIngredients > 0 ? $successCount / $totalIngredients : 0.0;
        $verified = $verificationRate > 0.5;

        $matchedIngredients = array_filter($verifiedIngredients, fn (VerifiedIngredientData $i): bool => $i->matched);
        $sources = array_map(fn (VerifiedIngredientData $i): string => $i->nutritionPer100g?->source ?? '', $matchedIngredients);
        $sources = array_filter($sources);
        /** @var array<string, int<1, max>> $sourceCount */
        $sourceCount = array_count_values($sources);
        arsort($sourceCount);
        $primarySource = array_key_first($sourceCount) ?? 'mixed';

        return new IngredientVerificationResultData(
            verifiedIngredients: VerifiedIngredientData::collect($verifiedIngredients, DataCollection::class),
            totalVerified: $successCount,
            verificationSuccess: $verified,
            verificationRate: $verificationRate,
            verified: $verified,
            source: $primarySource,
        );
    }

    private function verifyIngredient(string $ingredientName, IngredientSpecificity $specificity, ?string $barcode): ?NutritionWithSourceData
    {
        $searchResults = $this->foodDataProvider->searchWithSpecificity($ingredientName, $specificity, $barcode);

        if ($searchResults === []) {
            return null;
        }

        $bestMatch = $searchResults[0] ?? null;

        if ($bestMatch === null) {
            return null; // @codeCoverageIgnore
        }

        return new NutritionWithSourceData(
            calories: $bestMatch['calories'],
            protein: $bestMatch['protein'],
            carbs: $bestMatch['carbs'],
            fat: $bestMatch['fat'],
            fiber: $bestMatch['fiber'],
            sugar: $bestMatch['sugar'],
            sodium: $bestMatch['sodium'],
            source: $bestMatch['source'],
        );
    }
}
