<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\MemoryStorageException;

interface UpdateMemoryTool
{
    /**
     * Update an existing memory's content, metadata, or importance.
     *
     * @param  string  $memoryId  The ID of the memory to update.
     * @param  string|null  $content  New content (null to keep existing). Will regenerate embedding if changed.
     * @param  array<string, mixed>|null  $metadata  New metadata to merge with existing.
     * @param  int|null  $importance  New importance score 1-10 (null to keep existing).
     * @return bool True if the update was successful.
     *
     * @throws MemoryNotFoundException When the memory ID does not exist.
     * @throws MemoryStorageException When the update operation fails.
     */
    public function __invoke(
        string $memoryId,
        ?string $content = null,
        ?array $metadata = null,
        ?int $importance = null,
    ): bool;
}
