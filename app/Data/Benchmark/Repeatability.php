<?php

declare(strict_types=1);

namespace App\Data\Benchmark;

use Spatie\LaravelData\Data;

final class Repeatability extends Data
{
    public function __construct(
        public ?float $calories,
        public ?float $carbs,
        public ?float $protein,
        public ?float $fat,
        public int $mealsMeasured,
    ) {}
}
