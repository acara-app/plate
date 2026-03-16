<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

interface CategorizeMemoriesTool
{
    /**
     * @param  array<string>  $memoryIds  List of memory IDs to categorize.
     * @param  bool  $persistCategories  Whether to save categories to the memories (default: true).
     * @return array<string, array<string>|null> MemoryID => [Categories], or null if memory not found.
     */
    public function execute(array $memoryIds, bool $persistCategories = true): array;
}
