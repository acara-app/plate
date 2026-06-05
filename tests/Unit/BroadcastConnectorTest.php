<?php

declare(strict_types=1);

use App\Connectors\Broadcast\BroadcastConnector;
use App\Services\StreamAggregator;
use App\Services\StreamEventStore;
use Illuminate\Broadcasting\AnonymousEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Streaming\Events\TextDelta;

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
        ->with('conversation-1', $event, 0);

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
