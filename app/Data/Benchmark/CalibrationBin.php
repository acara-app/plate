<?php

declare(strict_types=1);

namespace App\Data\Benchmark;

use Spatie\LaravelData\Data;

final class CalibrationBin extends Data
{
    public function __construct(
        public int $minConfidence,
        public int $maxConfidence,
        public int $sampleCount,
        public ?float $medianAbsoluteCarbError,
        public ?float $medianAbsoluteCarbErrorPercent,
    ) {}
}
