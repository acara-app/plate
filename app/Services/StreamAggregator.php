<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ChatStreamResult;
use Laravel\Ai\Streaming\Events\Citation;
use Laravel\Ai\Streaming\Events\Error as StreamError;
use Laravel\Ai\Streaming\Events\ProviderToolEvent;
use Laravel\Ai\Streaming\Events\ReasoningDelta;
use Laravel\Ai\Streaming\Events\ReasoningEnd;
use Laravel\Ai\Streaming\Events\ReasoningStart;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Laravel\Ai\Streaming\Events\StreamStart;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\TextEnd;
use Laravel\Ai\Streaming\Events\TextStart;
use Laravel\Ai\Streaming\Events\ToolCall;
use Laravel\Ai\Streaming\Events\ToolResult;

final readonly class StreamAggregator
{
    /**
     * @return array<string, mixed>
     */
    public function normalizeEvent(StreamEvent $event): array
    {
        return match (true) {
            $event instanceof StreamStart => [
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'stream_start',
                'provider' => $event->provider,
                'model' => $event->model,
                'timestamp' => $event->timestamp,
                'metadata' => $event->metadata,
            ],
            $event instanceof StreamEnd => [
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'stream_end',
                'reason' => $event->reason,
                'usage' => $event->usage->toArray(),
                'timestamp' => $event->timestamp,
            ],
            $event instanceof TextStart => [
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'text_start',
                'message_id' => $event->messageId,
                'timestamp' => $event->timestamp,
            ],
            $event instanceof TextDelta => [
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'text_delta',
                'message_id' => $event->messageId,
                'delta' => $event->delta,
                'timestamp' => $event->timestamp,
            ],
            $event instanceof TextEnd => [
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'text_complete',
                'message_id' => $event->messageId,
                'timestamp' => $event->timestamp,
            ],
            $event instanceof ReasoningStart => [
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'thinking_start',
                'reasoning_id' => $event->reasoningId,
                'timestamp' => $event->timestamp,
            ],
            $event instanceof ReasoningDelta => [
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'thinking',
                'reasoning_id' => $event->reasoningId,
                'delta' => $event->delta,
                'summary' => $event->summary,
                'timestamp' => $event->timestamp,
            ],
            $event instanceof ReasoningEnd => [
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'thinking_complete',
                'reasoning_id' => $event->reasoningId,
                'summary' => $event->summary,
                'timestamp' => $event->timestamp,
            ],
            $event instanceof ToolCall => [
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'tool_call',
                'tool_id' => $event->toolCall->id,
                'tool_name' => $event->toolCall->name,
                'arguments' => $event->toolCall->arguments,
                'reasoning_id' => $event->toolCall->reasoningId,
                'timestamp' => $event->timestamp,
                'tool_call' => $event->toolCall->toArray(),
            ],
            $event instanceof ToolResult => [
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'tool_result',
                'tool_id' => $event->toolResult->id,
                'tool_name' => $event->toolResult->name,
                'result' => $event->toolResult->result,
                'successful' => $event->successful,
                'error' => $event->error,
                'timestamp' => $event->timestamp,
                'tool_result' => $event->toolResult->toArray(),
            ],
            $event instanceof ProviderToolEvent => [
                'id' => $event->id,
                'type' => 'provider_tool',
                'item_id' => $event->itemId,
                'tool_type' => $event->type,
                'tool_name' => $event->type,
                'status' => $event->status,
                'data' => $event->data,
                'timestamp' => $event->timestamp,
            ],
            $event instanceof Citation => [
                ...$event->toArray(),
                'type' => 'citation',
            ],
            $event instanceof StreamError => [
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'error',
                'error_type' => $event->type,
                'message' => $event->message,
                'recoverable' => $event->recoverable,
                'timestamp' => $event->timestamp,
                'metadata' => $event->metadata,
            ],
            default => $event->toArray(),
        };
    }

    /**
     * @param  iterable<StreamEvent>  $events
     */
    public function aggregate(iterable $events): ChatStreamResult
    {
        $normalized = [];

        foreach ($events as $event) {
            $normalized[] = $this->normalizeEvent($event);
        }

        return $this->aggregateNormalized($normalized);
    }

    /**
     * @param  list<array{sequence: int, type: string, data: array<string, mixed>}>  $storedEvents
     */
    public function aggregateStoredEvents(array $storedEvents): ChatStreamResult
    {
        return $this->aggregateNormalized(array_values(array_map(
            fn (array $event): array => $event['data'],
            $storedEvents,
        )));
    }

    /**
     * @param  list<array<string, mixed>>  $events
     */
    private function aggregateNormalized(array $events): ChatStreamResult
    {
        $text = collect($events)
            ->filter(fn (array $event): bool => ($event['type'] ?? null) === 'text_delta')
            ->map(fn (array $event): string => (string) ($event['delta'] ?? ''))
            ->join('');

        return new ChatStreamResult(
            text: $text,
            toolCalls: $this->toolCalls($events),
            toolResults: $this->toolResults($events),
            providerTools: $this->providerTools($events),
            citations: $this->citations($events),
            errors: $this->errors($events),
            usage: $this->usage($events),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return list<array<string, mixed>>
     */
    private function toolCalls(array $events): array
    {
        return collect($events)
            ->filter(fn (array $event): bool => ($event['type'] ?? null) === 'tool_call')
            ->map(fn (array $event): array => $event['tool_call'])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return list<array<string, mixed>>
     */
    private function toolResults(array $events): array
    {
        return collect($events)
            ->filter(fn (array $event): bool => ($event['type'] ?? null) === 'tool_result')
            ->map(fn (array $event): array => $event['tool_result'])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return list<array<string, mixed>>
     */
    private function providerTools(array $events): array
    {
        return collect($events)
            ->filter(fn (array $event): bool => ($event['type'] ?? null) === 'provider_tool')
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return list<array<string, mixed>>
     */
    private function citations(array $events): array
    {
        return collect($events)
            ->filter(fn (array $event): bool => ($event['type'] ?? null) === 'citation')
            ->map(fn (array $event): array => is_array($event['citation'] ?? null) ? $event['citation'] : [])
            ->filter(fn (array $citation): bool => $citation !== [])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return list<array<string, mixed>>
     */
    private function errors(array $events): array
    {
        return collect($events)
            ->filter(fn (array $event): bool => ($event['type'] ?? null) === 'error')
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return array<string, mixed>
     */
    private function usage(array $events): array
    {
        $event = collect($events)
            ->reverse()
            ->first(fn (array $event): bool => ($event['type'] ?? null) === 'stream_end'
                && is_array($event['usage'] ?? null));

        if (! is_array($event) || ! is_array($event['usage'] ?? null)) {
            return [];
        }

        return $event['usage'];
    }
}
