<?php

declare(strict_types=1);

namespace App\DataObjects\Memory;

use Spatie\LaravelData\Data;

/**
 * Represents a single semantic memory record.
 */
final class MemoryData extends Data
{
    /**
     * @param  string  $id  Unique identifier for the memory.
     * @param  string  $content  The natural language content of the memory.
     * @param  array<string, mixed>  $metadata  Contextual tags and attributes.
     * @param  int  $importance  Priority score from 1-10.
     * @param  array<string>  $categories  Semantic categories assigned to this memory.
     * @param  string  $createdAt  ISO 8601 timestamp of when the memory was created.
     * @param  string|null  $updatedAt  ISO 8601 timestamp of last update.
     * @param  string|null  $expiresAt  ISO 8601 timestamp of when memory expires.
     * @param  bool  $isArchived  Whether the memory is in cold storage.
     */
    public function __construct(
        public string $id,
        public string $content,
        public array $metadata,
        public int $importance,
        public array $categories,
        public string $createdAt,
        public ?string $updatedAt = null,
        public ?string $expiresAt = null,
        public bool $isArchived = false,
    ) {}
}
