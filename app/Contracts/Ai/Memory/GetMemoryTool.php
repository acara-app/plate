<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\DataObjects\Memory\MemoryData;

interface GetMemoryTool
{
    /**
     * Retrieve a single memory by its ID.
     *
     * @param  string  $memoryId  The unique identifier of the memory.
     * @param  bool  $includeArchived  Whether to retrieve archived memories.
     * @return MemoryData The requested memory.
     *
     * @throws MemoryNotFoundException When the memory ID does not exist.
     */
    public function execute(string $memoryId, bool $includeArchived = false): MemoryData;
}
