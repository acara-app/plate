<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\MemoryStorageException;

interface LinkMemoriesTool
{
    /**
     * Create bidirectional links between related memories.
     *
     * Links allow building a knowledge graph where memories can reference
     * each other with typed relationships. Common relationship types:
     * - 'related': General semantic relationship
     * - 'contradicts': Memories that conflict with each other
     * - 'follows': Temporal or logical sequence
     * - 'refines': More detailed version of another memory
     * - 'supersedes': Replaces outdated information
     *
     * @param  array<string>  $memoryIds  Memories to link together (minimum 2).
     * @param  string  $relationship  Type of relationship.
     * @param  bool  $bidirectional  Whether links work both ways (default: true).
     * @return bool True if links were created successfully.
     *
     * @throws MemoryNotFoundException When any memory ID does not exist.
     * @throws MemoryStorageException When the linking operation fails.
     */
    public function __invoke(
        array $memoryIds,
        string $relationship = 'related',
        bool $bidirectional = true,
    ): bool;
}
