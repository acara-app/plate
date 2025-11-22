<?php

declare(strict_types=1);

namespace App\Services;

use App\DataObjects\FoodSearchResultData;
use App\DataObjects\NutritionData;
use App\DataObjects\UsdaFoodData;
use App\Models\UsdaFoundationFood;
use App\Models\UsdaSrLegacyFood;
use Illuminate\Support\Facades\Cache;

final readonly class UsdaFoodDataService
{
    private int $cacheMinutes;

    public function __construct()
    {
        $cacheMinutes = config('services.usda.cache_minutes', 10080);
        $this->cacheMinutes = is_int($cacheMinutes) ? $cacheMinutes : 10080;
    }

    /**
     * @return list<FoodSearchResultData>|null
     */
    public function searchFoods(string $query, int $pageSize = 5): ?array
    {
        $cacheKey = "usda:search:v3:{$query}:{$pageSize}";

        /** @var list<FoodSearchResultData>|null $result */
        $result = Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($query, $pageSize): ?array {
            // Search Foundation Foods first (preferred)
            $foundation = UsdaFoundationFood::query()
                ->where('description', 'LIKE', "%{$query}%")
                ->limit($pageSize)
                ->get(['id', 'description']);

            $results = [];

            foreach ($foundation as $food) {
                $results[] = FoodSearchResultData::from([
                    'id' => (string) $food->id,
                    'name' => $food->description,
                    'brand' => null,
                    'dataType' => 'Foundation',
                ]);
            }

            // If we need more results, search SR Legacy
            $remaining = $pageSize - count($results);
            if ($remaining > 0) {
                $legacy = UsdaSrLegacyFood::query()
                    ->where('description', 'LIKE', "%{$query}%")
                    ->limit($remaining)
                    ->get(['id', 'description']);

                foreach ($legacy as $food) {
                    $results[] = FoodSearchResultData::from([
                        'id' => (string) $food->id,
                        'name' => $food->description,
                        'brand' => null,
                        'dataType' => 'SR Legacy',
                    ]);
                }
            }

            return $results !== [] ? $results : null;
        });

        return $result;
    }

    public function getFoodById(string $fdcId): ?UsdaFoodData
    {
        $cacheKey = "usda:food:v2:{$fdcId}";

        /** @var UsdaFoodData|null $result */
        $result = Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($fdcId): ?UsdaFoodData {
            // Try Foundation Foods first
            $food = UsdaFoundationFood::query()->find($fdcId);
            $dataType = 'Foundation';

            // If not found, try SR Legacy
            if (! $food) {
                $food = UsdaSrLegacyFood::query()->find($fdcId);
                $dataType = 'SR Legacy';
            }

            if (! $food) {
                return null;
            }

            return UsdaFoodData::from([
                'fdcId' => (string) $food->id,
                'description' => $food->description,
                'brandOwner' => null,
                'dataType' => $dataType,
                'foodNutrients' => $food->nutrients ?? [],
            ]);
        });

        return $result;
    }

    public function extractNutritionPer100g(UsdaFoodData $foodData): NutritionData
    {
        $nutrients = $foodData->foodNutrients;

        $nutrientMap = [];
        foreach ($nutrients as $nutrient) {
            if (
                ! is_array($nutrient)
                || ! is_array($nutrient['nutrient'] ?? null)
                || ! is_string($nutrient['nutrient']['number'] ?? null)
                || ! is_numeric($nutrient['amount'] ?? null)
            ) {
                continue; // @codeCoverageIgnore
            }

            $nutrientMap[$nutrient['nutrient']['number']] = (float) $nutrient['amount'];
        }

        return NutritionData::from([
            'calories' => $nutrientMap['208'] ?? null, // @phpstan-ignore nullCoalesce.offset
            'protein' => $nutrientMap['203'] ?? null, // @phpstan-ignore nullCoalesce.offset
            'carbs' => $nutrientMap['205'] ?? null, // @phpstan-ignore nullCoalesce.offset
            'fat' => $nutrientMap['204'] ?? null, // @phpstan-ignore nullCoalesce.offset
            'fiber' => $nutrientMap['291'] ?? null, // @phpstan-ignore nullCoalesce.offset
            'sugar' => $nutrientMap['269'] ?? null, // @phpstan-ignore nullCoalesce.offset
            'sodium' => $nutrientMap['307'] ?? null, // @phpstan-ignore nullCoalesce.offset
        ]);
    }

    public function cleanIngredientName(string $name): string
    {
        $modifiers = [
            'fresh', 'organic', 'raw', 'cooked', 'grilled', 'baked', 'fried',
            'frozen', 'canned', 'dried', 'smoked', 'roasted', 'steamed',
            'boneless', 'skinless', 'whole', 'sliced', 'diced', 'chopped',
            'extra virgin', 'virgin', 'refined', 'unrefined',
            'low fat', 'non-fat', 'full fat', 'reduced fat',
            'unsalted', 'salted', 'unsweetened', 'sweetened',
        ];

        $cleaned = mb_strtolower(mb_trim($name));

        foreach ($modifiers as $modifier) {
            $cleaned = preg_replace('/\b'.preg_quote($modifier, '/').'\b/i', '', $cleaned) ?? $cleaned;
        }

        $cleaned = preg_replace('/\([^)]*\)/', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s+/', ' ', $cleaned) ?? $cleaned;

        return mb_trim($cleaned);
    }
}
