<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class ExtractedIngredientData extends Data
{
    public function __construct(
        public string $name,
        public string $quantity,
        public int $day,
        public string $meal,
    ) {}
}
