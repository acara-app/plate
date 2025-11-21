<?php

declare(strict_types=1);

namespace App\DataObjects;

final readonly class OpenFoodFactsSearchResultData
{
    /**
     * @param  array<int, OpenFoodFactsProductData>  $products
     */
    public function __construct(
        public int $count,
        public int $page,
        public int $pageSize,
        public array $products,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $products = [];

        if (isset($data['products']) && is_array($data['products'])) {
            foreach ($data['products'] as $productData) {
                if (is_array($productData)) {
                    /** @var array<string, mixed> $productData */
                    $products[] = OpenFoodFactsProductData::fromArray($productData);
                }
            }
        }

        return new self(
            count: isset($data['count']) && is_int($data['count']) ? $data['count'] : 0,
            page: isset($data['page']) && is_int($data['page']) ? $data['page'] : 1,
            pageSize: isset($data['page_size']) && is_int($data['page_size']) ? $data['page_size'] : 0,
            products: $products,
        );
    }

    public function getBestMatch(): ?OpenFoodFactsProductData
    {
        return $this->products[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->products === [];
    }
}
