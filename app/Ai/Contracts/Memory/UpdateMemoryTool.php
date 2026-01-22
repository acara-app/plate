<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

interface UpdateMemoryTool
{
    /**
     * Update an existing memory's content or metadata.
     *
     * @param  string  $memoryId  The ID of the memory to update.
     * @param  string|null  $content  New content (null to keep existing).
     * @param  array<string, mixed>|null  $metadata  New metadata to merge/overwrite.
     * @return bool True if the update was successful.
     */
    public function __invoke(
        string $memoryId,
        ?string $content = null,
        ?array $metadata = null
    ): bool;
}
