<?php

declare(strict_types=1);

namespace App\Data\Benchmark;

use App\Enums\Benchmark\AnalysisPath;
use Spatie\LaravelData\Data;

final class PathMetrics extends Data
{
    public function __construct(
        public AnalysisPath $path,
        public int $failedRuns,
        public BenchmarkMetrics $metrics,
    ) {}
}
