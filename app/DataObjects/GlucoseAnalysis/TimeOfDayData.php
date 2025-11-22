<?php

declare(strict_types=1);

namespace App\DataObjects\GlucoseAnalysis;

use Spatie\LaravelData\Data;

final class TimeOfDayData extends Data
{
    public function __construct(
        public TimeOfDayPeriodData $morning,
        public TimeOfDayPeriodData $afternoon,
        public TimeOfDayPeriodData $evening,
        public TimeOfDayPeriodData $night,
    ) {}
}
