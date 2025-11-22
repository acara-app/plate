<?php

declare(strict_types=1);

namespace App\DataObjects\GlucoseAnalysis;

use Spatie\LaravelData\Data;

final class DateRangeData extends Data
{
    public function __construct(
        public ?string $start,
        public ?string $end,
    ) {}
}
