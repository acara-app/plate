<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Exceptions\Memory\InvalidMemoryFilterException;
use App\Ai\Exceptions\Memory\MemoryNotFoundException;

interface DeleteMemoryTool
{
    /**
     * @param  string|null  $memoryId  Specific ID to delete.
     * @param  array<string, mixed>  $filter  If $memoryId is null, delete all matching this filter (must be non-empty).
     * @return int Number of memories deleted.
     *
     * @throws InvalidMemoryFilterException When both $memoryId is null and $filter is empty.
     * @throws MemoryNotFoundException When $memoryId is provided but does not exist.
     */
    public function execute(?string $memoryId = null, array $filter = []): int;
}
