<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\MemoryStorageException;

interface ArchiveMemoriesTool
{
    /**
     * @param  array<string>  $memoryIds  Memories to archive.
     * @return int Number of memories archived.
     *
     * @throws MemoryNotFoundException When any memory ID does not exist.
     * @throws MemoryStorageException When the archive operation fails.
     */
    public function execute(array $memoryIds): int;
}
