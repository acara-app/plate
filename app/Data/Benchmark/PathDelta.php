<?php

declare(strict_types=1);

namespace App\Data\Benchmark;

use App\Enums\Benchmark\AnalysisPath;
use Spatie\LaravelData\Data;

final class PathDelta extends Data
{
    public function __construct(
        public AnalysisPath $path,
        public ?float $carbMae,
        public ?float $carbMape,
        public ?float $energyMape,
        public ?float $itemRecall,
    ) {}
}
