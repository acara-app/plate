<?php

declare(strict_types=1);

namespace App\Services;

use App\DataObjects\FoodSearchResultData;
use App\DataObjects\NutritionData;
use App\DataObjects\UsdaFoodData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class UsdaFoodDataService
{
    private string $apiKey;

    private string $baseUrl;

    private int $cacheMinutes;

    public function __construct()
    {
        $apiKey = config('services.usda.api_key');
        $baseUrl = config('services.usda.url', 'https://api.nal.usda.gov/fdc/v1');
        $cacheMinutes = config('services.usda.cache_minutes', 10080);

        $this->apiKey = is_string($apiKey) ? $apiKey : '';
        $this->baseUrl = is_string($baseUrl) ? $baseUrl : 'https://api.nal.usda.gov/fdc/v1';
        $this->cacheMinutes = is_int($cacheMinutes) ? $cacheMinutes : 10080;
    }

    /**
     * @return list<FoodSearchResultData>|null
     */
    public function searchFoods(string $query, int $pageSize = 5): ?array
    {
        if ($this->apiKey === '' || $this->apiKey === '0') {
            Log::warning('USDA API key not configured');

            return null;
        }

        $cacheKey = "usda:search:v2:{$query}:{$pageSize}";

        /** @var list<FoodSearchResultData>|null $result */
        $result = Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($query, $pageSize): ?array {
            try {
                if (! $this->checkRateLimit()) {
                    return null; // @codeCoverageIgnore
                }

                $response = Http::timeout(10)
                    ->get("{$this->baseUrl}/foods/search", [
                        'api_key' => $this->apiKey,
                        'query' => $query,
                        'dataType' => ['Foundation', 'SR Legacy'],
                        'pageSize' => $pageSize,
                        'sortBy' => 'dataType.keyword',
                        'sortOrder' => 'asc',
                    ]);

                if ($response->successful()) {
                    /** @var array<string, mixed> $data */
                    $data = $response->json();

                    if (! isset($data['foods']) || ! is_array($data['foods'])) {
                        return null; // @codeCoverageIgnore
                    }

                    /** @var array<int, array<string, mixed>> $foods */
                    $foods = $data['foods'];

                    return array_map(fn (array $food): FoodSearchResultData => FoodSearchResultData::from([
                        'id' => is_int($food['fdcId'] ?? null) || is_string($food['fdcId'] ?? null) ? (string) $food['fdcId'] : '',
                        'name' => is_string($food['description'] ?? null) ? $food['description'] : '',
                        'brand' => is_string($food['brandOwner'] ?? null) ? $food['brandOwner'] : null,
                        'dataType' => is_string($food['dataType'] ?? null) ? $food['dataType'] : '',
                    ]), $foods);
                }

                return null;
            } catch (ConnectionException) {
                return null; // @codeCoverageIgnore
            }
        });

        return $result;
    }

    public function getFoodById(string $fdcId): ?UsdaFoodData
    {
        if ($this->apiKey === '' || $this->apiKey === '0') {
            return null;
        }

        $cacheKey = "usda:food:{$fdcId}";

        /** @var UsdaFoodData|null $result */
        $result = Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($fdcId): ?UsdaFoodData {
            try {
                $response = Http::timeout(10)
                    ->get("{$this->baseUrl}/food/{$fdcId}", [
                        'api_key' => $this->apiKey,
                    ]);

                if ($response->successful()) {
                    /** @var array<string, mixed> $data */
                    $data = $response->json();

                    return UsdaFoodData::from([
                        'fdcId' => is_int($data['fdcId'] ?? null) || is_string($data['fdcId'] ?? null) ? (string) $data['fdcId'] : $fdcId,
                        'description' => is_string($data['description'] ?? null) ? $data['description'] : '',
                        'brandOwner' => is_string($data['brandOwner'] ?? null) ? $data['brandOwner'] : null,
                        'dataType' => is_string($data['dataType'] ?? null) ? $data['dataType'] : '',
                        'foodNutrients' => is_array($data['foodNutrients'] ?? null) ? $data['foodNutrients'] : [],
                    ]);
                }

                return null;
            } catch (ConnectionException) {
                return null;
            }
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

        return NutritionData::fromArray([
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

    private function checkRateLimit(): bool
    {
        $key = 'usda:rate_limit:search';
        $requests = Cache::get($key, 0);

        if ($requests >= 800) { // @codeCoverageIgnore
            return false; // @codeCoverageIgnore
        } // @codeCoverageIgnore

        Cache::put($key, is_int($requests) ? $requests + 1 : 1, now()->addHour());

        return true;
    }
}
