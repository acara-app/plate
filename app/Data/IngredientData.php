<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class IngredientData extends Data
{
    public function __construct(
        public string $name,
        public string $quantity,
        public ?string $specificity = null,
        public ?string $barcode = null,
    ) {}
}
