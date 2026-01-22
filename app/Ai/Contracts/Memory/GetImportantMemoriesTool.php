<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

interface GetImportantMemoriesTool
{
    /**
     * Retrieve high-priority memories, optionally filtered by context.
     *
     * @param  int  $threshold  Minimum importance score (e.g., 8/10).
     * @param  int  $limit  Max results.
     * @return array<int, array{id: string, content: string, importance: int}>
     */
    public function __invoke(int $threshold = 8, int $limit = 10): array;
}
