<?php

declare(strict_types=1);

use App\Services\BroadcastConnector;
use App\Services\StreamAggregator;
use App\Services\StreamEventStore;
use Illuminate\Broadcasting\AnonymousEvent;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Step;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall as ToolCallEvent;
use Laravel\Ai\Streaming\Events\ToolResult as ToolResultEvent;
use Pusher\ApiErrorException;
use Pusher\Pusher;

covers(BroadcastConnector::class);

it('delivers stream events through redis replay and broadcast', function (): void {
    Event::fake([AnonymousEvent::class]);

    $event = new TextDelta('event-1', 'message-1', 'Hello', now()->timestamp);
    $events = Mockery::mock(StreamEventStore::class);
    $events->shouldReceive('wasCancellationRequested')
        ->once()
        ->with('conversation-1')
        ->andReturnFalse();
    $events->shouldReceive('append')
        ->once()
        ->withArgs(fn (string $conversationId, array $payload, int $sequence): bool => $conversationId === 'conversation-1'
            && $payload['type'] === 'text_delta'
            && $payload['delta'] === 'Hello'
            && $sequence === 0);

    $aggregator = resolve(StreamAggregator::class);

    $delivery = new BroadcastConnector($events, $aggregator)->deliver(
        stream: [$event],
        userId: 1,
        conversationId: 'conversation-1',
    );

    expect($delivery->cancelled)->toBeFalse()
        ->and($delivery->result->text)->toBe('Hello');

    Event::assertDispatched(
        AnonymousEvent::class,
        fn (AnonymousEvent $event): bool => $event->broadcastAs() === 'text_delta'
            && $event->shouldBroadcastNow()
            && $event->broadcastOn()[0] instanceof PrivateChannel
            && $event->broadcastOn()[0]->name === 'private-chat.1'
            && $event->broadcastWith()['id'] === 'event-1'
            && $event->broadcastWith()['type'] === 'text_delta'
            && $event->broadcastWith()['message_id'] === 'message-1'
            && $event->broadcastWith()['delta'] === 'Hello',
    );
});

it('stops delivery before storing or broadcasting when cancellation is requested', function (): void {
    Event::fake([AnonymousEvent::class]);

    $event = new TextDelta('event-1', 'message-1', 'Hello', now()->timestamp);

    $events = Mockery::mock(StreamEventStore::class);
    $events->shouldReceive('wasCancellationRequested')
        ->once()
        ->with('conversation-1')
        ->andReturnTrue();
    $events->shouldNotReceive('append');

    $aggregator = resolve(StreamAggregator::class);

    $delivery = new BroadcastConnector($events, $aggregator)->deliver(
        stream: [$event],
        userId: 1,
        conversationId: 'conversation-1',
    );

    expect($delivery->cancelled)->toBeTrue()
        ->and($delivery->result->hasAssistantContent())->toBeFalse();

    Event::assertNotDispatched(AnonymousEvent::class);
});

it('holds back sub-agent progress so a specialist tool only reports its final result', function (): void {
    Event::fake([AnonymousEvent::class]);

    $toolCall = new ToolCall('call-1', 'nutrition_specialist', ['task' => 'Plan lunch']);
    $step = new Step('Here is lunch.', [$toolCall], [], FinishReason::Stop, new TextUsage, new Meta('openai', 'gpt-5-mini'), '', []);

    $stream = [
        new ToolCallEvent('event-1', $toolCall, 1),
        new ToolResultEvent('event-2', new ToolResult('call-1', 'nutrition_specialist', [], 'Grilled'), true, null, 1, preliminary: true),
        new ToolResultEvent('event-3', new ToolResult('call-1', 'nutrition_specialist', [], 'Grilled salmon'), true, null, 1),
        new StreamEnd('event-4', 'stop', new TextUsage, 1, new Collection([$step])),
    ];

    $events = Mockery::mock(StreamEventStore::class);
    $events->shouldReceive('wasCancellationRequested')->andReturnFalse();
    $events->shouldReceive('append')->times(3);

    $delivery = new BroadcastConnector($events, resolve(StreamAggregator::class))->deliver(
        stream: $stream,
        userId: 1,
        conversationId: 'conversation-1',
    );

    expect($delivery->result->toolResults)->toHaveCount(1)
        ->and($delivery->result->toolResults[0]['result'])->toBe('Grilled salmon')
        ->and($delivery->steps->all())->toBe([$step]);

    Event::assertDispatchedTimes(AnonymousEvent::class, 3);
});

it('delivers one successful chart and a plain text fallback when the model omits its takeaway', function (): void {
    Event::fake([AnonymousEvent::class]);
    $events = $this->spy(StreamEventStore::class);
    $aggregator = resolve(StreamAggregator::class);
    $summary = json_encode([
        'success' => true,
        'date_range' => ['from' => '2026-11-09', 'to' => '2026-11-15'],
        'summaries' => [['type' => 'stepCount', 'date' => '2026-11-09', 'primary_value' => 0]],
    ], JSON_THROW_ON_ERROR);

    $result = fn (string $id): ToolResult => new ToolResult($id, 'show_weekly_steps', [], $summary);
    $stream = [
        new ToolResultEvent('preliminary', $result('call-1'), true, null, 1, preliminary: true),
        new ToolResultEvent('failed', $result('call-2'), false, 'Unavailable', 2),
        new ToolResultEvent('valid', $result('call-3'), true, null, 3),
        new ToolResultEvent('again', $result('call-4'), true, null, 4),
        new StreamEnd('end', 'stop', new TextUsage, 5),
    ];

    $delivery = new BroadcastConnector($events, $aggregator)->deliver($stream, 1, 'conversation-1');

    $broadcasts = Event::dispatched(AnonymousEvent::class)->map(fn (array $arguments): array => $arguments[0]->broadcastWith());
    expect($broadcasts->pluck('type')->all())->toBe(['tool_result', 'tool_result', 'chat_visual', 'tool_result', 'text_delta', 'stream_end']);
    $visual = $broadcasts[2];
    expect($visual['part']['id'])->toBe('call-3');
    expect($delivery->result->text)->toContain('2026-11-09: 0', '2026-11-10: no reading');
    expect($broadcasts[4]['delta'])->toBe($delivery->result->text);

    $interrupted = $aggregator->aggregateStoredEvents([['sequence' => 2, 'type' => 'chat_visual', 'data' => $visual]])->withoutPendingApprovals();
    expect($interrupted->text)->toBe($delivery->result->text);
    expect($interrupted->visualPart)->toBe($visual['part']);
});

it('keeps streaming and reports the error when the websocket server rejects a broadcast', function (): void {
    Exceptions::fake();

    $pusher = Mockery::mock(Pusher::class);
    $pusher->shouldReceive('trigger')
        ->twice()
        ->andThrow(new ApiErrorException('Payload too large.', 413));

    Broadcast::extend('rejecting', fn (): PusherBroadcaster => new PusherBroadcaster($pusher));
    config([
        'broadcasting.default' => 'rejecting',
        'broadcasting.connections.rejecting' => ['driver' => 'rejecting'],
    ]);

    $events = Mockery::mock(StreamEventStore::class);
    $events->shouldReceive('wasCancellationRequested')->andReturnFalse();
    $events->shouldReceive('append')->twice();

    $delivery = new BroadcastConnector($events, resolve(StreamAggregator::class))->deliver(
        stream: [
            new TextDelta('event-1', 'message-1', 'Hello ', now()->timestamp),
            new TextDelta('event-2', 'message-1', 'there', now()->timestamp),
        ],
        userId: 1,
        conversationId: 'conversation-1',
    );

    expect($delivery->cancelled)->toBeFalse()
        ->and($delivery->result->text)->toBe('Hello there');

    Exceptions::assertReported(
        fn (BroadcastException $exception): bool => $exception->getMessage() === 'Pusher error: Payload too large..',
    );
});
