<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final class ChatProcessing implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function __construct(
        public readonly int $userId,
        public readonly string $conversationId,
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
        return 'processing';
    }

    /**
     * @return array{conversationId: string}
     */
    public function broadcastWith(): array
    {
        return ['conversationId' => $this->conversationId];
    }
}
