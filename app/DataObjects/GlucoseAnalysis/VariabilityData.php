<?php

declare(strict_types=1);

namespace App\DataObjects\GlucoseAnalysis;

use Spatie\LaravelData\Data;

final class VariabilityData extends Data
{
    public function __construct(
        public ?float $stdDev,
        public ?float $coefficientOfVariation,
        public ?string $classification,
    ) {}
}
