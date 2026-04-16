<?php

declare(strict_types=1);

namespace App\Data\Memory;

use Spatie\LaravelData\Data;

final class ConsolidationResultData extends Data
{
    /**
     * @param  int  $memoriesConsolidated  Total source memories folded into merges.
     * @param  int  $memoriesCreated  Number of new consolidated memories created.
     * @param  int  $clustersKeptSeparate  Clusters the decider rejected.
     * @param  int  $errors  Count of failures during processing.
     * @param  array<int, array<string, mixed>>  $details  Per-cluster trace for debugging.
     */
    public function __construct(
        public int $clustersProcessed,
        public int $memoriesConsolidated,
        public int $memoriesCreated,
        public int $clustersKeptSeparate,
        public int $errors,
        public array $details,
    ) {}

    public static function none(): self
    {
        return new self(0, 0, 0, 0, 0, []);
    }
}
