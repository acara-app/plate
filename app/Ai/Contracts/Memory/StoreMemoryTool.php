<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

interface StoreMemoryTool
{
    /**
     * Store a new semantic memory.
     *
     * @param  string  $content  The natural language content of the memory.
     * @param  array<string, mixed>  $metadata  Contextual tags (e.g., ['source' => 'chat', 'user_id' => 12]).
     * @param  array<float>|null  $vector  Optional pre-computed embedding vector.
     * @param  int  $importance  Score from 1-10 indicating memory priority.
     * @return string The unique ID of the stored memory.
     */
    public function __invoke(
        string $content,
        array $metadata = [],
        ?array $vector = null,
        int $importance = 1
    ): string;
}
