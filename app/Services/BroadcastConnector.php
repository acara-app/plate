<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ChatStreamDelivery;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Str;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Laravel\Ai\Streaming\Events\StreamStart;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\TextEnd;
use Laravel\Ai\Streaming\Events\ToolApprovalRequest;
use Laravel\Ai\Streaming\Events\ToolResult;

/**
 * @phpstan-import-type TNormalizedEvent from StreamAggregator
 * @phpstan-import-type TComposedEvent from ReplyComposer
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
    public function deliver(iterable $stream, int $userId, string $conversationId, ?ReplyComposer $composer = null): ChatStreamDelivery
    {
        $channel = ChatChannel::private($userId);
        $sequence = 0;
        $cancelled = false;
        $lastCancellationCheck = 0.0;
        $payloads = [];
        $steps = new Collection;
        $provider = null;
        $textTemplate = null;

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

            $payload = $this->aggregator->normalizeEvent($event);

            if ($event instanceof TextDelta) {
                $textTemplate = $payload;
            }

            foreach ($this->outgoing($event, $payload, $composer, $textTemplate, $conversationId) as $outgoing) {
                $payloads[] = $outgoing;
                $this->publish($outgoing, $conversationId, $sequence++, $channel);
            }
        }

        if ($cancelled && $composer instanceof ReplyComposer) {
            foreach ($this->composed($composer->cancel(), (string) Str::ulid(), time(), $textTemplate, $conversationId) as $outgoing) {
                $payloads[] = $outgoing;
                $this->publish($outgoing, $conversationId, $sequence++, $channel);
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
     * @param  TNormalizedEvent|null  $textTemplate
     * @return list<TNormalizedEvent>
     */
    private function outgoing(StreamEvent $event, array $payload, ?ReplyComposer $composer, ?array $textTemplate, string $conversationId): array
    {
        if (! $composer instanceof ReplyComposer) {
            return [$payload];
        }

        if ($event instanceof TextDelta) {
            return $this->composed($composer->feed($event->delta), $event->id, $event->timestamp, $textTemplate, $conversationId);
        }

        $flushed = $event instanceof TextEnd || $event instanceof ToolApprovalRequest || $event instanceof StreamEnd
            ? $this->composed($composer->finish(), $event->id, $event->timestamp, $textTemplate, $conversationId)
            : [];

        return [...$flushed, $payload];
    }

    /**
     * @param  list<TComposedEvent>  $composed
     * @param  TNormalizedEvent|null  $textTemplate
     * @return list<TNormalizedEvent>
     */
    private function composed(array $composed, string $sourceId, int $timestamp, ?array $textTemplate, string $conversationId): array
    {
        $payloads = [];

        foreach ($composed as $index => $event) {
            $id = $sourceId.'.'.($index + 1);

            $payloads[] = match ($event['type']) {
                'text' => [...$textTemplate ?? [], 'id' => $id, 'type' => 'text_delta', 'delta' => $event['text']],
                'visual_patch' => [
                    'id' => $id,
                    'type' => 'visual_patch',
                    'conversation_id' => $conversationId,
                    'visual_id' => $event['visual_id'],
                    'patches' => $event['patches'],
                    'timestamp' => $timestamp,
                ],
                'visual_end' => [
                    'id' => $id,
                    'type' => 'visual_end',
                    'conversation_id' => $conversationId,
                    'visual_id' => $event['visual_id'],
                    'accepted' => $event['accepted'],
                    'fallback_text' => $event['fallback_text'],
                    'reason' => $event['reason'],
                    'timestamp' => $timestamp,
                ],
            };
        }

        return $payloads;
    }

    /**
     * @param  TNormalizedEvent  $payload
     */
    private function publish(array $payload, string $conversationId, int $sequence, PrivateChannel $channel): void
    {
        $payload = [...$payload, 'conversation_id' => $conversationId, 'sequence' => $sequence];

        $this->events->append($conversationId, $payload, $sequence);
        $this->broadcastEvent($payload, $channel);
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
