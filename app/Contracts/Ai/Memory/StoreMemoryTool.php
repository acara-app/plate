<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Exceptions\Memory\MemoryStorageException;
use DateTimeInterface;

interface StoreMemoryTool
{
    /**
     * Store a new semantic memory.
     *
     * The memory will be embedded and stored in the vector database for
     * semantic search. Categories can be assigned automatically or manually.
     *
     * @param  string  $content  The natural language content of the memory.
     * @param  array<string, mixed>  $metadata  Contextual tags (e.g., ['source' => 'chat', 'user_id' => 12]).
     * @param  array<float>|null  $vector  Optional pre-computed embedding vector (null = auto-compute).
     * @param  int  $importance  Score from 1-10 indicating memory priority.
     * @param  array<string>  $categories  Initial categories to assign.
     * @param  DateTimeInterface|null  $expiresAt  When memory should expire (null = never).
     * @return string The unique ID of the stored memory.
     *
     * @throws MemoryStorageException When the storage operation fails.
     */
    public function execute(
        string $content,
        array $metadata = [],
        ?array $vector = null,
        int $importance = 1,
        array $categories = [],
        ?DateTimeInterface $expiresAt = null,
    ): string;
}
