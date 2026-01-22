<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

interface GetMemoryStatTool
{
    /**
     * Get statistics about the memory store.
     *
     * @return array{
     * total_memories: int,
     * last_update: string,
     * categories_count: array<string, int>,
     * storage_size_mb: float
     * }
     */
    public function __invoke(): array;
}
