<?php

declare(strict_types=1);

namespace App\Data\Benchmark;

use App\Data\NutrientValues;
use Spatie\LaravelData\Data;

final class PredictedRun extends Data
{
    public function __construct(
        public NutrientValues $values,
        public int $confidence,
        public ?float $itemRecall = null,
        public ?float $itemPrecision = null,
    ) {}
}
