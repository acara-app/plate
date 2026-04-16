<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Services\Null\NullMemoryContext;
use Illuminate\Container\Attributes\Bind;

#[Bind(NullMemoryContext::class)]
interface ManagesMemoryContext
{
    /**
     * @param  array<int, array{role: string, content: string}>  $conversationTail
     */
    public function render(int $userId, string $userMessage, array $conversationTail = []): string;
}
