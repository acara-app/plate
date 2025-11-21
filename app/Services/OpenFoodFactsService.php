<?php

declare(strict_types=1);

namespace App\Services;

use App\DataObjects\NutritionData;
use App\DataObjects\OpenFoodFactsProductData;
use App\DataObjects\OpenFoodFactsSearchResultData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class OpenFoodFactsService
{
    private string $baseUrl;

    private int $cacheMinutes;

    private string $userAgent;

    public function __construct()
    {
        $baseUrl = config('services.openfoodfacts.url', 'https://world.openfoodfacts.org');
        $cacheMinutes = config('services.openfoodfacts.cache_minutes', 10080);
        $userAgent = config('services.openfoodfacts.user_agent', 'AcaraPlate/1.0 (https://github.com/acara-app/plate)');

        $this->baseUrl = is_string($baseUrl) ? $baseUrl : 'https://world.openfoodfacts.org';
        $this->cacheMinutes = is_int($cacheMinutes) ? $cacheMinutes : 10080;
        $this->userAgent = is_string($userAgent) ? $userAgent : 'AcaraPlate/1.0 (https://github.com/acara-app/plate)';
    }

    public function searchProduct(string $query, int $pageSize = 5): ?OpenFoodFactsSearchResultData
    {
        $cacheKey = "openfoodfacts:search:v2:{$query}:{$pageSize}";

        /** @var OpenFoodFactsSearchResultData|null $result */
        $result = Cache::remember($cacheKey, now()->addDays(30), function () use ($query, $pageSize): ?OpenFoodFactsSearchResultData {
            try {
                // Check rate limit before making request
                if (! $this->checkRateLimit()) {
                    Log::warning('OpenFoodFacts rate limit reached', ['query' => $query]);

                    return null;
                }

                $response = Http::timeout(10)
                    ->withUserAgent($this->userAgent)
                    ->get("{$this->baseUrl}/api/v2/search", [
                        'categories_tags_en' => $this->inferCategory($query),
                        'fields' => 'code,product_name,brands,nutriments,serving_size,serving_quantity,nutrition_grade_fr,ingredients_text',
                        'page_size' => $pageSize,
                        'sort_by' => 'unique_scans_n', // Sort by popularity
                    ]);

                if ($response->successful()) {
                    /** @var array<string, mixed> $data */
                    $data = $response->json();

                    return OpenFoodFactsSearchResultData::fromArray($data);
                }

                Log::warning('OpenFoodFacts search failed', [
                    'query' => $query,
                    'status' => $response->status(),
                ]);

                return null;
            } catch (ConnectionException $e) {
                Log::error('OpenFoodFacts connection failed', [
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });

        return $result;
    }

    public function getProductByBarcode(string $barcode): ?OpenFoodFactsProductData
    {
        $cacheKey = "openfoodfacts:barcode:{$barcode}";

        /** @var OpenFoodFactsProductData|null $result */
        $result = Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($barcode): ?OpenFoodFactsProductData {
            try {
                $response = Http::timeout(10)
                    ->withUserAgent($this->userAgent)
                    ->get("{$this->baseUrl}/api/v2/product/{$barcode}");

                if ($response->successful()) {
                    /** @var array<string, mixed> $data */
                    $data = $response->json();

                    if (isset($data['status']) && $data['status'] === 1 && isset($data['product'])) {
                        /** @var array<string, mixed> $productData */
                        $productData = $data['product'];

                        return OpenFoodFactsProductData::fromArray($productData);
                    }
                }

                return null;
            } catch (ConnectionException) {
                return null;
            }
        });

        return $result;
    }

    public function extractNutritionPer100g(OpenFoodFactsProductData $product): ?NutritionData
    {
        $nutriments = $product->rawData['nutriments'] ?? null;

        if (! is_array($nutriments)) {
            return null;
        }

        /** @var array<string, mixed> $nutriments */
        return NutritionData::fromArray([
            'calories' => $this->getNutriment($nutriments, 'energy-kcal_100g'),
            'protein' => $this->getNutriment($nutriments, 'proteins_100g'),
            'carbs' => $this->getNutriment($nutriments, 'carbohydrates_100g'),
            'fat' => $this->getNutriment($nutriments, 'fat_100g'),
            'fiber' => $this->getNutriment($nutriments, 'fiber_100g'),
            'sugar' => $this->getNutriment($nutriments, 'sugars_100g'),
            'sodium' => $this->getNutriment($nutriments, 'sodium_100g'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $nutriments
     */
    private function getNutriment(array $nutriments, string $key): ?float
    {
        $value = $nutriments[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Check if we're within OpenFoodFacts rate limits (10 req/min for search)
     * We stay at 8 req/min to be safe.
     */
    private function checkRateLimit(): bool
    {
        $key = 'openfoodfacts:rate_limit:search';
        $requests = Cache::get($key, 0);

        if ($requests >= 8) { // Stay under 10/min limit
            return false;
        }

        Cache::put($key, is_int($requests) ? $requests + 1 : 1, now()->addMinute());

        return true;
    }

    /**
     * Infer OpenFoodFacts category from ingredient name for better search results.
     * Maps common ingredient patterns to OFF category tags.
     */
    private function inferCategory(string $ingredientName): string
    {
        $ingredientLower = mb_strtolower($ingredientName);

        // Category mapping based on OpenFoodFacts taxonomy
        $categoryMap = [
            // Proteins
            'chicken breast' => 'chicken-breasts',
            'chicken' => 'chickens',
            'beef' => 'beef-meat',
            'pork' => 'pork',
            'salmon' => 'salmons',
            'tuna' => 'tunas',
            'egg' => 'eggs',
            'tofu' => 'tofus',

            // Grains & Carbs
            'brown rice' => 'brown-rices',
            'white rice' => 'white-rices',
            'rice' => 'rices',
            'pasta' => 'pastas',
            'bread' => 'breads',
            'oats' => 'oat-flakes',
            'quinoa' => 'quinoas',

            // Oils & Fats
            'olive oil' => 'olive-oils',
            'coconut oil' => 'coconut-oils',
            'butter' => 'butters',
            'avocado' => 'avocados',

            // Dairy
            'milk' => 'milks',
            'cheese' => 'cheeses',
            'yogurt' => 'yogurts',
            'greek yogurt' => 'greek-yogurts',

            // Vegetables
            'broccoli' => 'broccolis',
            'spinach' => 'spinaches',
            'tomato' => 'tomatoes',
            'carrot' => 'carrots',
            'onion' => 'onions',

            // Fruits
            'apple' => 'apples',
            'banana' => 'bananas',
            'orange' => 'oranges',
            'berry' => 'berries',
            'strawberry' => 'strawberries',

            // Legumes
            'lentil' => 'lentils',
            'chickpea' => 'chickpeas',
            'black bean' => 'black-beans',
            'kidney bean' => 'kidney-beans',

            // Nuts & Seeds
            'almond' => 'almonds',
            'walnut' => 'walnuts',
            'peanut' => 'peanuts',
            'chia seed' => 'chia-seeds',
        ];

        // Check for exact matches first
        foreach ($categoryMap as $pattern => $category) {
            if (str_contains($ingredientLower, $pattern)) {
                return $category;
            }
        }

        // Fallback: return the cleaned ingredient name as category
        return $this->cleanIngredientName($ingredientLower);
    }

    /**
     * Clean ingredient name for better search results.
     * Removes common modifiers and qualifiers.
     */
    private function cleanIngredientName(string $name): string
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

        // Remove parenthetical content (e.g., "(boneless)")
        $cleaned = preg_replace('/\([^)]*\)/', '', $cleaned) ?? $cleaned;

        // Remove extra whitespace
        $cleaned = preg_replace('/\s+/', ' ', $cleaned) ?? $cleaned;

        return mb_trim($cleaned);
    }
}
