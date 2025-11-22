<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class OpenFoodFactsSearchResultData extends Data
{
    /**
     * @param  DataCollection<int, OpenFoodFactsProductData>|array<int, array<string, mixed>>  $products
     */
    public function __construct(
        public int $count = 0,
        public int $page = 1,
        public int $pageSize = 0,
        #[DataCollectionOf(OpenFoodFactsProductData::class)]
        public DataCollection|array $products = [],
    ) {
        if (is_array($this->products)) {
            $this->products = OpenFoodFactsProductData::collect($this->products, DataCollection::class);
        }
    }

    public function getBestMatch(): ?OpenFoodFactsProductData
    {
        /** @var DataCollection<int, OpenFoodFactsProductData> $products */
        $products = $this->products;

        return $products[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return count($this->products) === 0;
    }
}
