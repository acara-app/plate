<?php

declare(strict_types=1);

use App\Services\BroadcastConnector;
use App\Services\StreamAggregator;
use App\Services\StreamEventStore;
use Illuminate\Broadcasting\AnonymousEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
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
