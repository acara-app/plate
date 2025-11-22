<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class OpenFoodFactsProductData extends Data
{
    /**
     * @param  array<string, mixed>|null  $nutriments
     */
    public function __construct(
        public ?string $code,
        public ?string $productName,
        public ?string $brands,
        public ?string $ingredientsText,
        public ?string $servingSize,
        public ?float $servingQuantity,
        #[MapInputName('nutrition_grade_fr')]
        public ?string $nutritionGrade,
        public ?array $nutriments = null,
    ) {}
}
