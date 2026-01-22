<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

use App\DataObjects\Memory\MemorySearchResultData;

interface SearchMemoryTool
{
    /**
     * Semantically search for memories related to a query.
     *
     * Uses vector similarity search to find memories semantically related
     * to the provided query, regardless of exact keyword matches.
     *
     * @param  string  $query  The search query or question.
     * @param  int  $limit  Max number of results to return.
     * @param  float  $minRelevance  Minimum cosine similarity threshold (0.0 to 1.0).
     * @param  array<string, mixed>  $filter  Metadata filters (e.g., ['category' => 'preference']).
     * @param  bool  $includeArchived  Whether to include archived memories in search.
     * @return array<int, MemorySearchResultData> Ordered by relevance score descending.
     */
    public function __invoke(
        string $query,
        int $limit = 5,
        float $minRelevance = 0.7,
        array $filter = [],
        bool $includeArchived = false,
    ): array;
}
