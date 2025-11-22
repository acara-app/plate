<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class UsdaFoodData extends Data
{
    /**
     * @param  array<string, mixed>  $foodNutrients
     */
    public function __construct(
        public string $fdcId,
        public string $description,
        public ?string $brandOwner,
        public string $dataType,
        public array $foodNutrients,
    ) {}
}
