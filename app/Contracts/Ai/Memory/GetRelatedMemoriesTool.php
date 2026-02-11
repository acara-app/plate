<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\DataObjects\Memory\RelatedMemoryData;

interface GetRelatedMemoriesTool
{
    /**
     * Retrieve memories linked to a given memory (graph traversal).
     *
     * Traverses the memory knowledge graph starting from the specified memory,
     * returning all connected memories up to the specified depth.
     *
     * @param  string  $memoryId  The starting memory ID.
     * @param  int  $depth  How many levels of relationships to traverse.
     * @param  array<string>  $relationships  Filter by relationship types (empty = all types).
     * @param  bool  $includeArchived  Whether to include archived memories.
     * @return array<int, RelatedMemoryData> Connected memories with relationship info.
     *
     * @throws MemoryNotFoundException When the starting memory ID does not exist.
     */
    public function execute(
        string $memoryId,
        int $depth = 1,
        array $relationships = [],
        bool $includeArchived = false,
    ): array;
}
