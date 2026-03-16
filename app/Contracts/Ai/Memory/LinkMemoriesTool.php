<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\MemoryStorageException;

interface LinkMemoriesTool
{
    /**
     * @param  array<string>  $memoryIds  Memories to link together (minimum 2).
     * @param  string  $relationship  Type of relationship.
     * @param  bool  $bidirectional  Whether links work both ways (default: true).
     * @return bool True if links were created successfully.
     *
     * @throws MemoryNotFoundException When any memory ID does not exist.
     * @throws MemoryStorageException When the linking operation fails.
     */
    public function execute(
        array $memoryIds,
        string $relationship = 'related',
        bool $bidirectional = true,
    ): bool;
}
