<?php

declare(strict_types=1);

namespace App\Data\GlucoseAnalysis;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class DateRangeData extends Data
{
    public function __construct(
        public ?string $start,
        public ?string $end,
    ) {}
}
