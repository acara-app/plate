<?php

declare(strict_types=1);

namespace App\Data\Benchmark;

use Spatie\LaravelData\Data;

final class ItemizationAccuracy extends Data
{
    public function __construct(
        public ?float $recall,
        public ?float $precision,
        public int $mealsMeasured,
    ) {}
}
