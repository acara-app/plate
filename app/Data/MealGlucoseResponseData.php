<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class MealGlucoseResponseData extends Data
{
    public function __construct(
        public float $baseline,
        public float $peak,
        public float $delta,
        public int $readingsInWindow,
        public bool $overlapping,
    ) {}
}
