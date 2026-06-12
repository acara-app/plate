<?php

declare(strict_types=1);

namespace App\Data\Benchmark;

use Spatie\LaravelData\Data;

final class NutrientError extends Data
{
    public function __construct(
        public ?float $mae,
        public ?float $mape,
    ) {}
}
