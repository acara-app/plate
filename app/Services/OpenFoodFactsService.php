<?php

declare(strict_types=1);

namespace App\Services;

use App\DataObjects\NutritionData;
use App\DataObjects\OpenFoodFactsProductData;
use App\DataObjects\OpenFoodFactsSearchResultData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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
        $cacheKey = "openfoodfacts:search:{$query}:{$pageSize}";

        /** @var OpenFoodFactsSearchResultData|null $result */
        $result = Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () use ($query, $pageSize): ?OpenFoodFactsSearchResultData {
            try {
                $response = Http::timeout(10)
                    ->withUserAgent($this->userAgent)
                    ->get("{$this->baseUrl}/cgi/search.pl", [
                        'search_terms' => $query,
                        'page_size' => $pageSize,
                        'json' => 1,
                        'fields' => 'code,product_name,brands,nutriments,serving_size,serving_quantity,nutrition_grade_fr,ingredients_text',
                    ]);

                if ($response->successful()) {
                    /** @var array<string, mixed> $data */
                    $data = $response->json();

                    return OpenFoodFactsSearchResultData::fromArray($data);
                }

                return null;
            } catch (ConnectionException) {
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
}
