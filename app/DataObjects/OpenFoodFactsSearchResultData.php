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
     * @param  DataCollection<int, OpenFoodFactsProductData>  $products
     */
    public function __construct(
        public int $count = 0,
        public int $page = 1,
        public int $pageSize = 0,
        #[DataCollectionOf(OpenFoodFactsProductData::class)]
        public DataCollection $products = new DataCollection(OpenFoodFactsProductData::class, []),
    ) {}

    public function getBestMatch(): ?OpenFoodFactsProductData
    {
        return $this->products[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return count($this->products) === 0;
    }
}
