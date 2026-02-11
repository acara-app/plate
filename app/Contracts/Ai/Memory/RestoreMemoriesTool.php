<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\MemoryStorageException;

interface RestoreMemoriesTool
{
    /**
     * Restore archived memories back to active storage.
     *
     * Moves memories from cold storage back to active state,
     * making them available for normal search operations again.
     *
     * @param  array<string>  $memoryIds  Memories to restore.
     * @return int Number of memories restored.
     *
     * @throws MemoryNotFoundException When any memory ID does not exist.
     * @throws MemoryStorageException When the restore operation fails.
     */
    public function execute(array $memoryIds): int;
}
