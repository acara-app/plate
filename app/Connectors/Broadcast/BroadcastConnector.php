<?php

declare(strict_types=1);

namespace App\Connectors\Broadcast;

use App\Data\ChatStreamDelivery;
use App\Services\StreamAggregator;
use App\Services\StreamEventStore;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;
use Laravel\Ai\Streaming\Events\StreamEvent;

final readonly class BroadcastConnector
{
    public function __construct(
        private StreamEventStore $events,
        private StreamAggregator $aggregator,
    ) {}

    /**
     * @param  iterable<StreamEvent>  $stream
     */
    public function deliver(iterable $stream, int $userId, string $conversationId): ChatStreamDelivery
    {
        $channel = new PrivateChannel('chat.'.$userId);
        $sequence = 0;
        $cancelled = false;
        $streamEvents = [];

        foreach ($stream as $event) {
            if ($this->events->wasCancellationRequested($conversationId)) {
                $cancelled = true;

                break;
            }

            $streamEvents[] = $event;
            $this->events->append($conversationId, $event, $sequence++);
            $this->broadcastEvent($event, $channel);
        }

        return new ChatStreamDelivery(
            result: $this->aggregator->aggregate($streamEvents),
            cancelled: $cancelled,
        );
    }

    private function broadcastEvent(StreamEvent $event, PrivateChannel $channel): void
    {
        $payload = $this->aggregator->normalizeEvent($event);

        Broadcast::on($channel)
            ->as((string) $payload['type'])
            ->with($payload)
            ->sendNow();
    }
}
