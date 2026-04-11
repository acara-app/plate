<?php

declare(strict_types=1);

namespace App\Data\GlucoseAnalysis;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class GlucoseGoalsData extends Data
{
    public function __construct(
        public string $target,
        public string $reasoning,
    ) {}
}
