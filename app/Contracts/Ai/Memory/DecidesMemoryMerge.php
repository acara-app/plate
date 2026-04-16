<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Agents\Memory\MemoryMergeDeciderAgent;
use Illuminate\Container\Attributes\Bind;

#[Bind(MemoryMergeDeciderAgent::class)]
interface DecidesMemoryMerge
{
    /**
     * @return array{
     *     should_merge: bool,
     *     reasoning: string,
     *     synthesized_content: ?string,
     *     importance: ?int,
     *     categories: array<int, string>
     * }
     */
    public function decide(string $prompt): array;
}
