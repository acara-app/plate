<?php

declare(strict_types=1);

namespace App\Data\GlucoseAnalysis;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class VariabilityData extends Data
{
    public function __construct(
        public ?float $stdDev,
        public ?float $coefficientOfVariation,
        public ?string $classification,
    ) {}
}
