<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class DailyExerciseSummaryData extends Data
{
    public function __construct(
        public string $date,
        public float $totalMinutes,
    ) {}
}
