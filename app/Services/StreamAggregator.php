<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ChatStreamResult;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\Data\UrlCitation;
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

/**
 * @phpstan-type TNormalizedEvent array{type: string, delta?: string|null, tool_call?: array<string, mixed>, tool_result?: array<string, mixed>, citation?: array<string, mixed>, usage?: array<string, mixed>, ...<string, mixed>}
 */
final readonly class StreamAggregator
{
    /**
     * @var list<string>
     */
    private const array AGGREGATION_ONLY_KEYS = ['tool_call', 'tool_result', 'summary', 'metadata'];

    /**
     * @param  TNormalizedEvent  $payload
     * @return array{type: string, ...<string, mixed>}
     */
    public function broadcastPayload(array $payload): array
    {
        foreach (self::AGGREGATION_ONLY_KEYS as $key) {
            unset($payload[$key]);
        }

        return $payload;
    }

    /**
     * @return TNormalizedEvent
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
                'usage' => [
                    'prompt_tokens' => $event->usage->promptTokens,
                    'completion_tokens' => $event->usage->completionTokens,
                    'cache_write_input_tokens' => $event->usage->cacheWriteInputTokens,
                    'cache_read_input_tokens' => $event->usage->cacheReadInputTokens,
                    'reasoning_tokens' => $event->usage->reasoningTokens,
                ],
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
                'title' => $this->toolTitle($event->toolCall->name),
                'arguments' => $event->toolCall->arguments,
                'reasoning_id' => $event->toolCall->reasoningId,
                'timestamp' => $event->timestamp,
                'tool_call' => [
                    'id' => $event->toolCall->id,
                    'name' => $event->toolCall->name,
                    'arguments' => $event->toolCall->arguments,
                    'result_id' => $event->toolCall->resultId,
                    'reasoning_id' => $event->toolCall->reasoningId,
                    'reasoning_summary' => $event->toolCall->reasoningSummary,
                ],
            ],
            $event instanceof ToolResult => [
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'tool_result',
                'tool_id' => $event->toolResult->id,
                'tool_name' => $event->toolResult->name,
                'title' => $this->toolTitle($event->toolResult->name),
                'result' => $event->toolResult->result,
                'successful' => $event->successful,
                'error' => $event->error,
                'timestamp' => $event->timestamp,
                'tool_result' => [
                    'id' => $event->toolResult->id,
                    'name' => $event->toolResult->name,
                    'arguments' => $event->toolResult->arguments,
                    'result' => $event->toolResult->result,
                    'result_id' => $event->toolResult->resultId,
                ],
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
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'citation',
                'message_id' => $event->messageId,
                'citation' => $event->citation instanceof UrlCitation
                    ? ['title' => $event->citation->title, 'url' => $event->citation->url]
                    : [],
                'timestamp' => $event->timestamp,
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
            default => [
                'invocation_id' => $event->invocationId,
                'type' => $event->type(),
            ],
        };
    }

    /**
     * @param  list<array{sequence: int, type: string, data: TNormalizedEvent}>  $storedEvents
     */
    public function aggregateStoredEvents(array $storedEvents): ChatStreamResult
    {
        return $this->aggregateNormalized(array_map(
            fn (array $event): array => $event['data'],
            $storedEvents,
        ));
    }

    /**
     * @param  list<TNormalizedEvent>  $events
     */
    public function aggregateNormalized(array $events): ChatStreamResult
    {
        $text = implode('', array_map(
            fn (array $event): string => $event['delta'] ?? '',
            $this->ofType($events, 'text_delta'),
        ));

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

    private function toolTitle(string $name): string
    {
        $key = 'tools.' . $name;

        if (Lang::has($key)) {
            return (string) __($key);
        }

        return Str::ucfirst(str_replace('_', ' ', $name));
    }

    /**
     * @param  list<TNormalizedEvent>  $events
     * @return list<array<string, mixed>>
     */
    private function toolCalls(array $events): array
    {
        return array_map(
            fn (array $event): array => $event['tool_call'] ?? [],
            $this->ofType($events, 'tool_call'),
        );
    }

    /**
     * @param  list<TNormalizedEvent>  $events
     * @return list<array<string, mixed>>
     */
    private function toolResults(array $events): array
    {
        return array_map(
            fn (array $event): array => $event['tool_result'] ?? [],
            $this->ofType($events, 'tool_result'),
        );
    }

    /**
     * @param  list<TNormalizedEvent>  $events
     * @return list<array<string, mixed>>
     */
    private function providerTools(array $events): array
    {
        return $this->ofType($events, 'provider_tool');
    }

    /**
     * @param  list<TNormalizedEvent>  $events
     * @return list<array<string, mixed>>
     */
    private function citations(array $events): array
    {
        return array_values(array_filter(
            array_map(
                fn (array $event): array => $event['citation'] ?? [],
                $this->ofType($events, 'citation'),
            ),
            fn (array $citation): bool => $citation !== [],
        ));
    }

    /**
     * @param  list<TNormalizedEvent>  $events
     * @return list<array<string, mixed>>
     */
    private function errors(array $events): array
    {
        return $this->ofType($events, 'error');
    }

    /**
     * @param  list<TNormalizedEvent>  $events
     * @return array<string, mixed>
     */
    private function usage(array $events): array
    {
        foreach (array_reverse($this->ofType($events, 'stream_end')) as $event) {
            $usage = $event['usage'] ?? null;

            if (is_array($usage)) {
                return $usage;
            }
        }

        return [];
    }

    /**
     * @param  list<TNormalizedEvent>  $events
     * @return list<TNormalizedEvent>
     */
    private function ofType(array $events, string $type): array
    {
        return array_values(array_filter(
            $events,
            fn (array $event): bool => $event['type'] === $type,
        ));
    }
}
