<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\ApplyJsonPatch;
use App\Data\ChatStreamResult;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Laravel\Ai\Approvals\PendingApproval;
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
use Laravel\Ai\Streaming\Events\ToolApprovalRequest;
use Laravel\Ai\Streaming\Events\ToolCall;
use Laravel\Ai\Streaming\Events\ToolResult;
use stdClass;

/**
 * @phpstan-import-type TReplyPart from ChatStreamResult
 * @phpstan-import-type TVisualOutcome from ChatStreamResult
 *
 * @phpstan-type TNormalizedEvent array{type: string, delta?: string|null, tool_call?: array<string, mixed>, tool_result?: array<string, mixed>, citation?: array<string, mixed>, usage?: array<string, mixed>, approvals?: list<array{id: string, tool: string, arguments: array<string, mixed>, reason: string|null}>, ...<string, mixed>}
 */
final readonly class StreamAggregator
{
    /**
     * @var list<string>
     */
    private const array AGGREGATION_ONLY_KEYS = ['tool_call', 'tool_result', 'summary', 'metadata'];

    /**
     * @var list<string>
     */
    private const array TRUNCATABLE_BROADCAST_KEYS = ['result', 'arguments', 'data'];

    private const int MAX_BROADCAST_BYTES = 8_000;

    private const int TRUNCATED_PREVIEW_BYTES = 1_500;

    public function __construct(private ApplyJsonPatch $patcher) {}

    /**
     * @param  TNormalizedEvent  $payload
     * @return array{type: string, ...<string, mixed>}
     */
    public function broadcastPayload(array $payload): array
    {
        foreach (self::AGGREGATION_ONLY_KEYS as $key) {
            unset($payload[$key]);
        }

        if ($this->broadcastBytes($payload) <= self::MAX_BROADCAST_BYTES) {
            return $payload;
        }

        foreach (self::TRUNCATABLE_BROADCAST_KEYS as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = $this->truncatedPreview($payload[$key]);
                $payload['truncated'] = true;
            }
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
                    'input_tokens' => $event->usage->inputTokens,
                    'output_tokens' => $event->usage->outputTokens,
                    'cache_read_input_tokens' => $event->usage->cacheReadInputTokens,
                    'cache_write_input_tokens' => $event->usage->cacheWriteInputTokens,
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
                    'thought_signature' => $event->toolCall->thoughtSignature,
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
                'denied' => $event->toolResult->denied,
                'error' => $event->error,
                'timestamp' => $event->timestamp,
                'tool_result' => [
                    'id' => $event->toolResult->id,
                    'name' => $event->toolResult->name,
                    'arguments' => $event->toolResult->arguments,
                    'result' => $event->toolResult->result,
                    'result_id' => $event->toolResult->resultId,
                    'denied' => $event->toolResult->denied,
                    'failed' => $event->toolResult->failed,
                ],
            ],
            $event instanceof ToolApprovalRequest => [
                'id' => $event->id,
                'invocation_id' => $event->invocationId,
                'type' => 'tool_approval_request',
                'approvals' => array_values($event->pendingApprovals->map(
                    fn (PendingApproval $approval): array => [
                        'id' => $approval->id,
                        'tool' => $approval->tool,
                        'arguments' => $approval->arguments,
                        'reason' => $approval->reason,
                    ],
                )->all()),
                'timestamp' => $event->timestamp,
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
            // @codeCoverageIgnoreStart
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
            // @codeCoverageIgnoreEnd
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
        // @codeCoverageIgnoreStart
        return $this->aggregateNormalized(array_map(
            fn (array $event): array => $event['data'],
            $storedEvents,
        ));
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param  list<TNormalizedEvent>  $events
     */
    public function aggregateNormalized(array $events): ChatStreamResult
    {
        $reply = $this->reply($events);

        return new ChatStreamResult(
            text: $reply['text'],
            toolCalls: $this->toolCalls($events),
            toolResults: $this->toolResults($events),
            providerTools: $this->providerTools($events),
            citations: $this->citations($events),
            errors: $this->errors($events),
            usage: $this->usage($events),
            pendingApprovals: $this->pendingApprovals($events),
            parts: $reply['parts'],
            visuals: $reply['visuals'],
        );
    }

    /**
     * @param  list<TNormalizedEvent>  $events
     * @return array{text: string, parts: list<TReplyPart>, visuals: list<TVisualOutcome>}
     */
    private function reply(array $events): array
    {
        $text = '';
        $prose = '';
        $parts = [];
        $visuals = [];
        $patches = [];

        foreach ($events as $event) {
            if ($event['type'] === 'text_delta') {
                $text .= $event['delta'] ?? '';
                $prose .= $event['delta'] ?? '';
            }

            $visualId = $event['visual_id'] ?? null;

            if (! is_string($visualId)) {
                continue;
            }

            if ($event['type'] === 'visual_patch' && is_array($event['patches'] ?? null)) {
                $patches[$visualId] = [...$patches[$visualId] ?? [], ...array_values($event['patches'])];
            }

            if ($event['type'] !== 'visual_end') {
                continue;
            }

            $accepted = ($event['accepted'] ?? false) === true;
            $reason = $event['reason'] ?? null;
            $spec = $accepted ? $this->spec($patches[$visualId] ?? []) : new stdClass;
            $visuals[] = ['id' => $visualId, 'accepted' => $accepted, 'reason' => is_string($reason) ? $reason : null, ...$this->series($spec)];

            if (! $accepted) {
                continue;
            }

            $fallbackText = is_string($event['fallback_text'] ?? null) ? $event['fallback_text'] : '';
            $text .= mb_trim($fallbackText) === '' ? '' : $fallbackText.PHP_EOL;
            $parts = [...$parts, ...$this->proseParts($prose)];
            $prose = '';
            $parts[] = ['type' => 'data-spec', 'data' => [
                'id' => $visualId,
                'spec' => (string) json_encode($spec, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'fallback_text' => $fallbackText,
            ]];
        }

        return [
            'text' => $text,
            'parts' => $parts === [] ? [] : [...$parts, ...$this->proseParts($prose)],
            'visuals' => $visuals,
        ];
    }

    /**
     * @return list<array{type: 'text', text: string}>
     */
    private function proseParts(string $prose): array
    {
        return mb_trim($prose) === '' ? [] : [['type' => 'text', 'text' => $prose]];
    }

    /**
     * @param  list<mixed>  $patches
     */
    private function spec(array $patches): stdClass
    {
        $spec = new stdClass;
        $operations = json_decode((string) json_encode($patches));

        foreach (is_array($operations) ? $operations : [] as $operation) {
            if ($operation instanceof stdClass) {
                $spec = $this->patcher->handle($spec, $operation);
            }
        }

        return $spec instanceof stdClass ? $spec : new stdClass;
    }

    /**
     * @return array{bound: int, literal: int}
     */
    private function series(stdClass $spec): array
    {
        $series = ['bound' => 0, 'literal' => 0];
        $elements = $spec->elements ?? null;

        foreach ($elements instanceof stdClass ? get_object_vars($elements) : [] as $element) {
            $props = $element instanceof stdClass ? $element->props ?? null : null;
            $data = $props instanceof stdClass && in_array($element->type ?? null, ['BarChart', 'LineChart', 'Metric'], true) ? $props->data ?? null : null;
            $pointer = $data instanceof stdClass ? $data->{'$state'} ?? null : null;

            if (is_array($data)) {
                $series['literal']++;
            } elseif (is_string($pointer) && str_starts_with($pointer, '/data/')) {
                $series['bound']++;
            }
        }

        return $series;
    }

    /**
     * @param  list<TNormalizedEvent>  $events
     * @return array<string, string|null>
     */
    private function pendingApprovals(array $events): array
    {
        $pending = [];

        foreach ($this->ofType($events, 'tool_approval_request') as $event) {
            foreach ($event['approvals'] ?? [] as $approval) {
                $pending[$approval['id']] = $approval['reason'];
            }
        }

        return $pending;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function broadcastBytes(array $payload): int
    {
        return mb_strlen((string) json_encode((string) json_encode($payload)));
    }

    private function truncatedPreview(mixed $value): string
    {
        $text = is_string($value)
            ? $value
            : (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return mb_strcut($text, 0, self::TRUNCATED_PREVIEW_BYTES).'…';
    }

    private function toolTitle(string $name): string
    {
        $key = 'tools.'.$name;

        if (Lang::has($key)) {
            return (string) __($key); // @codeCoverageIgnore
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

        return []; // @codeCoverageIgnore
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
