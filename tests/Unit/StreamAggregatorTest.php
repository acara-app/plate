<?php

declare(strict_types=1);

use App\Services\StreamAggregator;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Streaming\Events\ProviderToolEvent;
use Laravel\Ai\Streaming\Events\ReasoningDelta;
use Laravel\Ai\Streaming\Events\StreamEnd;
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
    );

    $normalized = $aggregator->normalizeEvent($providerTool);

    $result = $aggregator->aggregate([
        new TextDelta('text-1', 'message-1', 'Hello ', $timestamp),
        new TextDelta('text-2', 'message-1', 'there', $timestamp),
        new ReasoningDelta('thinking-1', 'reasoning-1', 'checking', $timestamp),
        new ToolCallEvent('tool-event-1', new ToolCall('tool-1', 'lookup_health_metric', ['metric' => 'glucose']), $timestamp),
        new ToolResultEvent('tool-result-event-1', new ToolResult('tool-1', 'lookup_health_metric', [], ['value' => 104]), true, null, $timestamp),
        $providerTool,
        new StreamEnd('end-1', 'stop', new Usage(promptTokens: 10, completionTokens: 5), $timestamp),
    ]);

    expect($normalized['type'])->toBe('provider_tool')
        ->and($normalized['tool_type'])->toBe('web_search_call')
        ->and($result->text)->toBe('Hello there')
        ->and($result->toolCalls[0]['name'])->toBe('lookup_health_metric')
        ->and($result->toolResults[0]['result'])->toBe(['value' => 104])
        ->and($result->providerTools[0]['item_id'])->toBe('search-1')
        ->and($result->usage['prompt_tokens'])->toBe(10)
        ->and($result->usage['completion_tokens'])->toBe(5);
});
