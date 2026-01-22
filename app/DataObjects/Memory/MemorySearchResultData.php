<?php

declare(strict_types=1);

namespace App\DataObjects\Memory;

use Spatie\LaravelData\Data;

/**
 * Represents a memory search result with relevance score.
 */
final class MemorySearchResultData extends Data
{
    /**
     * @param  string  $id  Unique identifier for the memory.
     * @param  string  $content  The natural language content of the memory.
     * @param  float  $score  Cosine similarity score (0.0 to 1.0).
     * @param  array<string, mixed>  $metadata  Contextual tags and attributes.
     * @param  int  $importance  Priority score from 1-10.
     * @param  array<string>  $categories  Semantic categories assigned to this memory.
     */
    public function __construct(
        public string $id,
        public string $content,
        public float $score,
        public array $metadata,
        public int $importance,
        public array $categories = [],
    ) {}
}
