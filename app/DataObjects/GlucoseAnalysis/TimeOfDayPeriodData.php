<?php

declare(strict_types=1);

namespace App\DataObjects\GlucoseAnalysis;

use Spatie\LaravelData\Data;

final class TimeOfDayPeriodData extends Data
{
    public function __construct(
        public int $count,
        public ?float $average,
    ) {}
}
