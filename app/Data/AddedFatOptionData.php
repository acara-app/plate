<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class AddedFatOptionData extends Data
{
    public function __construct(
        public string $key,
        public float $grams,
        public float $calories,
        public float $protein,
        public float $carbs,
        public float $fat,
        public bool $selected,
    ) {}
}
