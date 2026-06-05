<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ChatStreamTurn
{
    public function __construct(
        public string $streamId,
        public string $userMessageId,
        public string $assistantMessageId,
    ) {}
}
