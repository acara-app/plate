<?php

declare(strict_types=1);

namespace App\DataObjects\GlucoseAnalysis;

use Spatie\LaravelData\Data;

final class GlucoseGoalsData extends Data
{
    public function __construct(
        public string $target,
        public string $reasoning,
    ) {}
}
