<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\MemoryStorageException;

interface RestoreMemoriesTool
{
    /**
     * @param  array<string>  $memoryIds  Memories to restore.
     * @return int Number of memories restored.
     *
     * @throws MemoryNotFoundException When any memory ID does not exist.
     * @throws MemoryStorageException When the restore operation fails.
     */
    public function execute(array $memoryIds): int;
}
