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
        public float $costUsd = 0.0,
        public ?float $p95LatencyMs = null,
        public int $unmeteredRuns = 0,
    ) {}

    public function costPerHundred(): ?float
    {
        return $this->metrics->runCount > 0 && $this->unmeteredRuns === 0 ? $this->costUsd / $this->metrics->runCount * 100 : null;
    }

    public function successRate(): float
    {
        $attempts = $this->metrics->runCount + $this->failedRuns;

        return $attempts > 0 ? $this->metrics->runCount / $attempts : 0.0;
    }
}
