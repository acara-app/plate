<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final class ChatStreamFailed implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function __construct(
        public readonly int $userId,
        public readonly string $conversationId,
        public readonly string $message,
    ) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'error';
    }

    /**
     * @return array{conversationId: string, message: string}
     */
    public function broadcastWith(): array
    {
        return [
            'conversationId' => $this->conversationId,
            'message' => $this->message,
        ];
    }
}
