<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final class ChatRetrying implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function __construct(
        public readonly int $userId,
        public readonly string $conversationId,
        public readonly int $attempt,
        public readonly int $maxAttempts,
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
        return 'retrying';
    }

    /**
     * @return array{conversationId: string, attempt: int, maxAttempts: int}
     */
    public function broadcastWith(): array
    {
        return [
            'conversationId' => $this->conversationId,
            'attempt' => $this->attempt,
            'maxAttempts' => $this->maxAttempts,
        ];
    }
}
