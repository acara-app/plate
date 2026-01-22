<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

interface SearchMemoryTool
{
    /**
     * Semantically search for memories related to a query.
     *
     * @param  string  $query  The search query or question.
     * @param  int  $limit  Max number of results to return.
     * @param  float  $minRelevance  Minimum cosine similarity threshold (0.0 to 1.0).
     * @param  array<string, mixed>  $filter  Metadata filters (e.g., ['category' => 'preference']).
     * @return array<int, array{id: string, content: string, score: float, metadata: array}>
     */
    public function __invoke(
        string $query,
        int $limit = 5,
        float $minRelevance = 0.7,
        array $filter = []
    ): array;
}
