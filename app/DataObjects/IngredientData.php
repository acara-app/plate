<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class IngredientData extends Data
{
    public function __construct(
        public string $name,
        public string $quantity,
        public ?string $specificity = null,
        public ?string $barcode = null,
    ) {}
}
