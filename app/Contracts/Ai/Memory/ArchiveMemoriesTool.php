<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\MemoryStorageException;

interface ArchiveMemoriesTool
{
    /**
     * Move memories to cold storage (soft delete).
     *
     * Archived memories are not deleted but are excluded from normal
     * search operations. Use this for memories that are no longer
     * immediately relevant but might be needed later.
     *
     * @param  array<string>  $memoryIds  Memories to archive.
     * @return int Number of memories archived.
     *
     * @throws MemoryNotFoundException When any memory ID does not exist.
     * @throws MemoryStorageException When the archive operation fails.
     */
    public function execute(array $memoryIds): int;
}
