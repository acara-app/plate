<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Agents\Memory\MemoryQueryGeneratorAgent;
use Illuminate\Container\Attributes\Bind;

#[Bind(MemoryQueryGeneratorAgent::class)]
interface GeneratesMemoryQueries
{
    /**
     * @return array<int, string> 2-4 semantic search queries.
     */
    public function generateQueries(string $conversationContext): array;
}
