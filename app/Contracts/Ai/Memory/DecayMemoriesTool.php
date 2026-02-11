<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

interface DecayMemoriesTool
{
    /**
     * Apply decay to old memories, reducing importance or archiving stale ones.
     *
     * Simulates human-like forgetting by gradually reducing the importance
     * of memories over time. Memories below a minimum importance threshold
     * can optionally be archived or deleted.
     *
     * This tool should typically be run on a schedule (e.g., daily) to
     * maintain a healthy memory system.
     *
     * @param  int  $ageThresholdDays  Memories older than this get decayed.
     * @param  float  $decayFactor  Multiply importance by this (e.g., 0.9 = 10% reduction).
     * @param  int  $minImportance  Minimum importance before archiving (0 = never archive).
     * @param  bool  $archiveDecayed  Whether to archive memories that fall below minImportance.
     * @return array{
     *     decayed_count: int,
     *     archived_count: int,
     *     avg_importance_before: float,
     *     avg_importance_after: float
     * }
     */
    public function execute(
        int $ageThresholdDays = 30,
        float $decayFactor = 0.9,
        int $minImportance = 1,
        bool $archiveDecayed = true,
    ): array;
}
