<?php

declare(strict_types=1);

namespace App\DataObjects;

final readonly class OpenFoodFactsProductData
{
    /**
     * @param  array<string, mixed>  $rawData
     */
    public function __construct(
        public ?string $code,
        public ?string $productName,
        public ?string $brands,
        public ?string $ingredientsText,
        public ?string $servingSize,
        public ?float $servingQuantity,
        public ?string $nutritionGrade,
        public array $rawData,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: isset($data['code']) && is_string($data['code']) ? $data['code'] : null,
            productName: isset($data['product_name']) && is_string($data['product_name']) ? $data['product_name'] : null,
            brands: isset($data['brands']) && is_string($data['brands']) ? $data['brands'] : null,
            ingredientsText: isset($data['ingredients_text']) && is_string($data['ingredients_text']) ? $data['ingredients_text'] : null,
            servingSize: isset($data['serving_size']) && is_string($data['serving_size']) ? $data['serving_size'] : null,
            servingQuantity: isset($data['serving_quantity']) && is_numeric($data['serving_quantity']) ? (float) $data['serving_quantity'] : null,
            nutritionGrade: isset($data['nutrition_grade_fr']) && is_string($data['nutrition_grade_fr']) ? $data['nutrition_grade_fr'] : null,
            rawData: $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawData;
    }
}
