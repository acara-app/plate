<?php

declare(strict_types=1);

namespace App\DataObjects\Memory;

use Spatie\LaravelData\Data;

/**
 * Statistics about the memory store.
 */
final class MemoryStatsData extends Data
{
    /**
     * @param  int  $totalMemories  Total count of memories in the store.
     * @param  int  $activeMemories  Count of non-archived memories.
     * @param  int  $archivedMemories  Count of archived/cold storage memories.
     * @param  string|null  $lastUpdate  ISO 8601 timestamp of last memory update.
     * @param  array<string, int>  $categoriesCount  Count by category.
     * @param  array<int, int>  $importanceDistribution  Count by importance level (1-10).
     * @param  float  $storageSizeMb  Approximate storage size in megabytes.
     * @param  int  $expiringCount  Count of memories expiring within 24 hours.
     */
    public function __construct(
        public int $totalMemories,
        public int $activeMemories,
        public int $archivedMemories,
        public ?string $lastUpdate,
        public array $categoriesCount,
        public array $importanceDistribution,
        public float $storageSizeMb,
        public int $expiringCount = 0,
    ) {}
}
