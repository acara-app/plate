<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Agents\Memory\MemoryExtractorAgent;
use Illuminate\Container\Attributes\Bind;

#[Bind(MemoryExtractorAgent::class)]
interface ExtractsMemoriesFromConversation
{
    /**
     * @return array{should_extract: bool, memories: array<int, array<string, mixed>>}
     */
    public function extractFromConversation(string $formattedConversation): array;
}
