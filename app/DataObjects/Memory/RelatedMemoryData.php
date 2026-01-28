<?php

declare(strict_types=1);

namespace App\DataObjects\Memory;

use Spatie\LaravelData\Data;

/**
 * Represents a related memory with relationship type.
 */
final class RelatedMemoryData extends Data
{
    /**
     * @param  string  $id  Unique identifier for the memory.
     * @param  string  $content  The natural language content of the memory.
     * @param  string  $relationship  Type of relationship (e.g., 'related', 'contradicts', 'follows').
     * @param  int  $depth  How many hops away from the source memory.
     * @param  array<string, mixed>  $metadata  Contextual tags and attributes.
     */
    public function __construct(
        public string $id,
        public string $content,
        public string $relationship,
        public int $depth,
        public array $metadata = [],
    ) {}
}
