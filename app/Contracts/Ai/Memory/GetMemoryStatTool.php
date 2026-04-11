<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Data\Memory\MemoryStatsData;

interface GetMemoryStatTool
{
    /**
     * @return MemoryStatsData Statistics about the memory store.
     */
    public function execute(): MemoryStatsData;
}
