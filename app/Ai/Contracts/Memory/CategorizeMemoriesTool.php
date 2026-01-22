<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

interface CategorizeMemoriesTool
{
    /**
     * Analyze and tag a list of memories with semantic categories.
     *
     * @param  array<string>  $memoryIds  List of memory IDs to categorize.
     * @return array<string, array<string>> Key-value pair of MemoryID => [Categories].
     */
    public function __invoke(array $memoryIds): array;
}
