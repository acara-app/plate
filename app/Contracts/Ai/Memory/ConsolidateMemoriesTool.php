<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\MemoryStorageException;

interface ConsolidateMemoriesTool
{
    /**
     * Merge multiple related memories into a single synthesized memory.
     *
     * Useful for de-duplicating facts, summarizing conversation history,
     * or combining related insights into a coherent memory.
     *
     * @param  array<string>  $memoryIds  IDs of memories to merge (minimum 2).
     * @param  string  $synthesizedContent  The new, consolidated content.
     * @param  array<string, mixed>|null  $metadata  Metadata for new memory (null = merge from sources).
     * @param  int|null  $importance  Importance score (null = max from sources).
     * @param  bool  $deleteOriginals  Whether to delete original memories (default: true).
     * @return string The ID of the new consolidated memory.
     *
     * @throws MemoryNotFoundException When any of the memory IDs do not exist.
     * @throws MemoryStorageException When the consolidation operation fails.
     */
    public function execute(
        array $memoryIds,
        string $synthesizedContent,
        ?array $metadata = null,
        ?int $importance = null,
        bool $deleteOriginals = true,
    ): string;
}
