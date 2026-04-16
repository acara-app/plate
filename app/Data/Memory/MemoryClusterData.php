<?php

declare(strict_types=1);

namespace App\Data\Memory;

use Spatie\LaravelData\Data;

final class MemoryClusterData extends Data
{
    /**
     * @param  int  $id  Internal cluster identifier (ephemeral, not persisted).
     * @param  array<int, string>  $memoryIds  ULIDs of the memories grouped together.
     * @param  array<int, array{id1: string, id2: string, similarity: float}>  $similarities  Pairwise similarity rows.
     * @param  float  $averageSimilarity  Mean pairwise similarity within the cluster.
     * @param  int  $maxGeneration  Highest consolidation_generation across the sources.
     */
    public function __construct(
        public int $id,
        public array $memoryIds,
        public array $similarities,
        public float $averageSimilarity,
        public int $maxGeneration,
    ) {}

    public function size(): int
    {
        return count($this->memoryIds);
    }
}
