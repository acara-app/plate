<?php

declare(strict_types=1);

namespace App\Data;

use App\Services\ChatChannel;

final readonly class ChatStreamTurn
{
    public function __construct(
        public string $streamId,
        public string $userMessageId,
        public string $assistantMessageId,
    ) {}

    /**
     * @return array{status: string, channel: string, conversationId: string, userMessageId: string, assistantMessageId: string}
     */
    public function acceptedPayload(int $userId, string $conversationId): array
    {
        return [
            'status' => 'processing',
            'channel' => ChatChannel::name($userId),
            'conversationId' => $conversationId,
            'userMessageId' => $this->userMessageId,
            'assistantMessageId' => $this->assistantMessageId,
        ];
    }
}
