<?php

declare(strict_types=1);

use App\Services\StreamAggregator;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Streaming\Events\ProviderToolEvent;
use Laravel\Ai\Streaming\Events\ReasoningDelta;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall as ToolCallEvent;
use Laravel\Ai\Streaming\Events\ToolResult as ToolResultEvent;

covers(StreamAggregator::class);

it('normalizes and aggregates rich Laravel AI stream events', function (): void {
    $timestamp = now()->timestamp;
    $aggregator = resolve(StreamAggregator::class);

    $providerTool = new ProviderToolEvent(
        id: 'provider-event-1',
        itemId: 'search-1',
        type: 'web_search_call',
        data: ['query' => 'glucose'],
        status: 'completed',
        timestamp: $timestamp,
        provider: 'openai',
    );

    $normalized = $aggregator->normalizeEvent($providerTool);

    $events = [
        new TextDelta('text-1', 'message-1', 'Hello ', $timestamp),
        new TextDelta('text-2', 'message-1', 'there', $timestamp),
        new ReasoningDelta('thinking-1', 'reasoning-1', 'checking', $timestamp),
        new ToolCallEvent('tool-event-1', new ToolCall('tool-1', 'lookup_health_metric', ['metric' => 'glucose'], thoughtSignature: 'sig-1'), $timestamp),
        new ToolResultEvent('tool-result-event-1', new ToolResult('tool-1', 'lookup_health_metric', [], ['value' => 104]), true, null, $timestamp),
        $providerTool,
        new StreamEnd('end-1', 'stop', new TextUsage(inputTokens: 10, outputTokens: 5), $timestamp),
    ];

    $result = $aggregator->aggregateNormalized(array_map(
        $aggregator->normalizeEvent(...),
        $events,
    ));

    expect($normalized['type'])->toBe('provider_tool')
        ->and($normalized['tool_type'])->toBe('web_search_call')
        ->and($result->text)->toBe('Hello there')
        ->and($result->toolCalls[0]['name'])->toBe('lookup_health_metric')
        ->and($result->toolCalls[0]['thought_signature'])->toBe('sig-1')
        ->and($result->toolResults[0]['result'])->toBe(['value' => 104])
        ->and($result->providerTools[0]['item_id'])->toBe('search-1')
        ->and($result->usage['input_tokens'])->toBe(10)
        ->and($result->usage['output_tokens'])->toBe(5);
});

it('trims oversized tool payloads so chat broadcasts fit the websocket message limit', function (StreamEvent $event, string $trimmedKey): void {
    $aggregator = resolve(StreamAggregator::class);

    $payload = $aggregator->broadcastPayload($aggregator->normalizeEvent($event));

    expect($payload[$trimmedKey])->toBeString()->toEndWith('…')
        ->and($payload['truncated'])->toBeTrue()
        ->and($payload['id'])->toBe($event->id)
        ->and(mb_strlen((string) json_encode((string) json_encode($payload))))->toBeLessThan(10_000);
})->with([
    'skill instructions returned by a tool' => [
        new ToolResultEvent('result-1', new ToolResult('tool-1', 'activate_skill', [], (string) json_encode([
            'success' => true,
            'instructions' => str_repeat("## Step\n\"Weigh\" every ingredient before logging it.\n", 600),
        ])), true, null, 1),
        'result',
    ],
    'non-latin tool arguments' => [
        new ToolCallEvent('call-1', new ToolCall('tool-1', 'log_health_entry', ['notes' => str_repeat('Өглөөний хоол ', 800)]), 1),
        'arguments',
    ],
    'provider search results' => [
        new ProviderToolEvent(
            id: 'provider-1',
            itemId: 'search-1',
            type: 'web_search_call',
            data: ['results' => array_fill(0, 200, ['url' => 'https://example.com/glycemic-index', 'title' => 'Glycemic index basics'])],
            status: 'completed',
            timestamp: 1,
            provider: 'openai',
        ),
        'data',
    ],
]);

it('broadcasts payloads under the limit in full without aggregation-only fields', function (): void {
    $aggregator = resolve(StreamAggregator::class);

    $payload = $aggregator->broadcastPayload($aggregator->normalizeEvent(
        new ToolResultEvent('result-1', new ToolResult('tool-1', 'lookup_health_metric', [], ['value' => 104]), true, null, 1),
    ));

    expect($payload['result'])->toBe(['value' => 104])
        ->and($payload)->not->toHaveKeys(['truncated', 'tool_result']);
});
