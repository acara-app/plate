<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

interface DeleteMemoryTool
{
    /**
     * Delete a specific memory or bulk delete by filter.
     *
     * @param  string|null  $memoryId  Specific ID to delete.
     * @param  array<string, mixed>  $filter  If $memoryId is null, delete all matching this filter.
     * @return int Number of memories deleted.
     */
    public function __invoke(?string $memoryId = null, array $filter = []): int;
}
