<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

use App\DataObjects\Memory\MemoryStatsData;

interface GetMemoryStatTool
{
    /**
     * Get statistics about the memory store.
     *
     * Returns comprehensive statistics including:
     * - Total, active, and archived memory counts
     * - Last update timestamp (null if no memories exist)
     * - Category distribution
     * - Importance level distribution
     * - Storage size and expiring memory count
     *
     * @return MemoryStatsData Statistics about the memory store.
     */
    public function __invoke(): MemoryStatsData;
}
