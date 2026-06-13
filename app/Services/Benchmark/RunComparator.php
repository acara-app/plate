<?php

declare(strict_types=1);

namespace App\Services\Benchmark;

use App\Data\Benchmark\BenchmarkMetrics;
use App\Data\Benchmark\HarnessReport;
use App\Data\Benchmark\PathDelta;
use App\Data\Benchmark\PathMetrics;

final class RunComparator
{
    /**
     * @return list<PathDelta>
     */
    public function compare(HarnessReport $current, HarnessReport $previous): array
    {
        $previousByPath = [];

        /** @var PathMetrics $pathMetrics */
        foreach ($previous->paths as $pathMetrics) {
            $previousByPath[$pathMetrics->path->value] = $pathMetrics->metrics;
        }

        $deltas = [];

        /** @var PathMetrics $pathMetrics */
        foreach ($current->paths as $pathMetrics) {
            $previousMetrics = $previousByPath[$pathMetrics->path->value] ?? null;

            if ($previousMetrics === null) {
                continue;
            }

            $deltas[] = $this->pathDelta($pathMetrics, $previousMetrics);
        }

        return $deltas;
    }

    private function pathDelta(PathMetrics $current, BenchmarkMetrics $previous): PathDelta
    {
        $metrics = $current->metrics;

        return new PathDelta(
            path: $current->path,
            carbMae: $this->delta($metrics->carbs->mae, $previous->carbs->mae),
            carbMape: $this->delta($metrics->carbs->mape, $previous->carbs->mape),
            energyMape: $this->delta($metrics->calories->mape, $previous->calories->mape),
            itemRecall: $this->delta($metrics->itemization->recall, $previous->itemization->recall),
        );
    }

    private function delta(?float $current, ?float $previous): ?float
    {
        if ($current === null || $previous === null) {
            return null;
        }

        return $current - $previous;
    }
}
