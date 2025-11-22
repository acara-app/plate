<?php

declare(strict_types=1);

namespace App\DataObjects\GlucoseAnalysis;

use Spatie\LaravelData\Data;

final class RangesData extends Data
{
    public function __construct(
        public ?float $min,
        public ?float $max,
    ) {}
}
