<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

use App\DataObjects\Memory\MemoryData;

interface GetImportantMemoriesTool
{
    /**
     * Retrieve high-priority memories, optionally filtered by context.
     *
     * Returns memories sorted by importance score descending.
     *
     * @param  int  $threshold  Minimum importance score (1-10).
     * @param  int  $limit  Max results to return.
     * @param  array<string>  $categories  Filter by specific categories (empty = all).
     * @param  bool  $includeArchived  Whether to include archived memories.
     * @return array<int, MemoryData> List of important memories.
     */
    public function execute(
        int $threshold = 8,
        int $limit = 10,
        array $categories = [],
        bool $includeArchived = false,
    ): array;
}
