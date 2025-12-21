<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class FoodItemData extends Data
{
    public function __construct(
        public string $name,
        public float $calories,
        public float $protein,
        public float $carbs,
        public float $fat,
        public string $portion,
    ) {}
}
