<?php

declare(strict_types=1);

namespace App\Data\GlucoseAnalysis;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class RangesData extends Data
{
    public function __construct(
        public ?float $min,
        public ?float $max,
    ) {}
}
