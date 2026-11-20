<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ChatStreamDelivery;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Broadcast;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Laravel\Ai\Streaming\Events\StreamStart;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolApprovalRequest;
use Laravel\Ai\Streaming\Events\ToolResult;

/**
 * @phpstan-import-type TNormalizedEvent from StreamAggregator
 */
final readonly class BroadcastConnector
{
    private const float CANCELLATION_CHECK_SECONDS = 0.25;

    public function __construct(
        private StreamEventStore $events,
        private StreamAggregator $aggregator,
    ) {}

    /**
     * @param  iterable<StreamEvent>  $stream
     */
    public function deliver(iterable $stream, int $userId, string $conversationId): ChatStreamDelivery
    {
        $channel = ChatChannel::private($userId);
        $cancelled = false;
        $lastCancellationCheck = 0.0;
        $payloads = [];
        $steps = new Collection;
        $provider = null;
        $visual = null;
        $hasText = false;

        foreach ($stream as $event) {
            if ($event instanceof StreamStart) {
                $provider = $event->provider;
            }

            if ($event instanceof StreamEnd || $event instanceof ToolApprovalRequest) {
                $steps = $event->steps;
            }

            if (microtime(true) - $lastCancellationCheck >= self::CANCELLATION_CHECK_SECONDS) {
                $lastCancellationCheck = microtime(true);

                if ($this->events->wasCancellationRequested($conversationId)) {
                    $cancelled = true;

                    break;
                }
            }

            if ($event instanceof ToolResult && $event->preliminary) {
                continue;
            }

            $batch = [$this->aggregator->normalizeEvent($event)];

            if ($event instanceof ToolResult && $visual === null) {
                $visual = $this->aggregator->visualEvent($event, $conversationId);
                if ($visual !== null) {
                    $batch[] = $visual;
                }
            }

            if ($event instanceof TextDelta && ! $hasText) {
                $hasText = mb_trim($event->delta) !== '';
            }

            if ($event instanceof StreamEnd && $visual !== null && ! $hasText) {
                $fallback = new TextDelta($event->id.':fallback', $visual['part']['id'], $visual['part']['data']['fallbackText'], $event->timestamp);
                $fallback->invocationId = $event->invocationId;
                array_unshift($batch, [...$this->aggregator->normalizeEvent($fallback), 'conversationId' => $conversationId]);
            }

            foreach ($batch as $payload) {
                $this->events->append($conversationId, $payload, count($payloads));
                $payloads[] = $payload;
                $this->broadcastEvent($payload, $channel);
            }
        }

        return new ChatStreamDelivery(
            result: $this->aggregator->aggregateNormalized($payloads),
            cancelled: $cancelled,
            steps: $steps,
            provider: $provider,
        );
    }

    /**
     * @param  TNormalizedEvent  $payload
     */
    private function broadcastEvent(array $payload, PrivateChannel $channel): void
    {
        try {
            Broadcast::on($channel)
                ->as($payload['type'])
                ->with($this->aggregator->broadcastPayload($payload))
                ->sendNow();
        } catch (BroadcastException $broadcastException) {
            report($broadcastException);
        }
    }
}
