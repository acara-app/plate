<?php

declare(strict_types=1);

namespace App\Contracts\Memory;

interface ManagesMemoryContext
{
    /**
     * @param  array<int, array{role: string, content: string}>  $conversationTail
     */
    public function render(int $userId, string $userMessage, array $conversationTail = []): string;
}
