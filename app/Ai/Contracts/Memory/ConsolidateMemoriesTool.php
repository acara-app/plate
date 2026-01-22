<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

interface ConsolidateMemoriesTool
{
    /**
     * Merge multiple related memories into a single synthesized memory.
     * Useful for de-duplicating facts or summarizing conversation history.
     *
     * @param  array<string>  $memoryIds  IDs of memories to merge.
     * @param  string  $synthesizedContent  The new, compressed content.
     * @return string The ID of the new consolidated memory (old ones are deleted).
     */
    public function __invoke(array $memoryIds, string $synthesizedContent): string;
}
