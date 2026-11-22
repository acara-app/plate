<?php

declare(strict_types=1);

use App\Actions\CompletePendingChatStreamTurn;
use App\Actions\CreatePendingChatStreamTurn;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use App\Services\BroadcastConnector;
use App\Services\ReplyComposer;
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
use Illuminate\Support\Sleep;
use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Step;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Streaming\Events\StreamEnd;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\TextEnd;
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

it('keeps a visual released before a stop and drops the one still open', function (): void {
    Event::fake([AnonymousEvent::class]);
    Sleep::fake(false);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    $turn = resolve(CreatePendingChatStreamTurn::class)->handle($conversation, $user, 'Plan my walk', [], 'web', catalogVersion: 1);

    $stream = (function (): Generator {
        yield new TextDelta('event-1', 'message-1', "Warm up first.\n".connectorTextVisual('Walk for 5 minutes')."Then:\n```spec\n", 1);
        Sleep::usleep(260_000);
        yield new TextDelta('event-2', 'message-1', '{"op":"add","path":"/root","value":"a"}'."\n", 1);
    })();

    $events = Mockery::mock(StreamEventStore::class);
    $events->shouldReceive('wasCancellationRequested')->andReturn(false, true);
    $events->shouldReceive('append');

    $delivery = new BroadcastConnector($events, resolve(StreamAggregator::class))
        ->deliver($stream, $user->id, $conversation->id, connectorComposer($turn->assistantMessageId));

    resolve(CompletePendingChatStreamTurn::class)->handle(
        conversationId: $conversation->id,
        user: $user,
        userMessageId: $turn->userMessageId,
        assistantMessageId: $turn->assistantMessageId,
        result: $delivery->result,
        status: History::STREAM_STATUS_CANCELLED,
    );

    $saved = History::query()->findOrFail($turn->assistantMessageId);

    expect($delivery->cancelled)->toBeTrue()
        ->and(array_column($saved->replyParts(), 'type'))->toBe(['text', 'data-spec', 'text'])
        ->and($saved->content)->toBe("Warm up first.\nWalk for 5 minutes\nThen:\n")
        ->and($saved->chatStreamMeta()['visuals'])->toBe([
            ['id' => "{$turn->assistantMessageId}-1", 'accepted' => true, 'reason' => null, 'bound' => 0, 'literal' => 0],
            ['id' => "{$turn->assistantMessageId}-2", 'accepted' => false, 'reason' => 'cancelled', 'bound' => 0, 'literal' => 0],
        ]);

    Event::assertDispatched(AnonymousEvent::class, fn (AnonymousEvent $event): bool => $event->broadcastAs() === 'visual_end'
        && $event->broadcastWith()['visual_id'] === "{$turn->assistantMessageId}-2"
        && $event->broadcastWith()['reason'] === 'cancelled');
});

it('composes a visual that follows a tool call in a new text block', function (): void {
    Event::fake([AnonymousEvent::class]);

    $toolCall = new ToolCall('call-1', 'get_user_profile', []);
    $events = Mockery::mock(StreamEventStore::class);
    $events->shouldReceive('wasCancellationRequested')->andReturnFalse();
    $events->shouldReceive('append');

    $delivery = new BroadcastConnector($events, resolve(StreamAggregator::class))->deliver(
        stream: [
            new TextDelta('event-1', 'message-1', 'Let me check your profile.', 1),
            new TextEnd('event-2', 'message-1', 1),
            new ToolCallEvent('event-3', $toolCall, 1),
            new ToolResultEvent('event-4', new ToolResult('call-1', 'get_user_profile', [], '{}'), true, null, 1),
            new TextDelta('event-5', 'message-2', connectorTextVisual('Walk after lunch.').'Enjoy it.', 1),
            new TextEnd('event-6', 'message-2', 1),
            new StreamEnd('event-7', 'stop', new TextUsage, 1),
        ],
        userId: 1,
        conversationId: 'conversation-1',
        composer: connectorComposer('reply'),
    );

    expect($delivery->result->text)->toBe("Let me check your profile.Walk after lunch.\nEnjoy it.")
        ->and($delivery->result->visuals)->toBe([['id' => 'reply-1', 'accepted' => true, 'reason' => null, 'bound' => 0, 'literal' => 0]])
        ->and(array_column($delivery->result->parts, 'type'))->toBe(['text', 'data-spec', 'text']);
});

it('sends the empty objects of a visual to the websocket as {}', function (): void {
    $sent = [];
    $pusher = Mockery::mock(Pusher::class);
    $pusher->shouldReceive('trigger')->andReturnUsing(function (array $channels, string $event, array $data) use (&$sent): object {
        $sent[] = ['event' => $event, 'json' => (string) json_encode($data)];

        return new stdClass;
    });

    Broadcast::extend('capturing', fn (): PusherBroadcaster => new PusherBroadcaster($pusher));
    config([
        'broadcasting.default' => 'capturing',
        'broadcasting.connections.capturing' => ['driver' => 'capturing'],
    ]);

    $events = Mockery::mock(StreamEventStore::class);
    $events->shouldReceive('wasCancellationRequested')->andReturnFalse();
    $events->shouldReceive('append');

    $visual = implode("\n", [
        '```spec',
        '{"op":"add","path":"/root","value":"stack"}',
        '{"op":"add","path":"/elements/stack","value":{"type":"Stack","props":{},"children":["line"]}}',
        '{"op":"add","path":"/elements/line","value":{"type":"Separator","props":{},"children":[]}}',
        '```',
    ]);

    new BroadcastConnector($events, resolve(StreamAggregator::class))->deliver(
        stream: [new TextDelta('event-1', 'message-1', $visual, 1), new TextEnd('event-2', 'message-1', 1)],
        userId: 1,
        conversationId: 'conversation-1',
        composer: connectorComposer('reply'),
    );

    $patches = implode('', array_column(array_filter($sent, fn (array $message): bool => $message['event'] === 'visual_patch'), 'json'));

    expect($patches)->toContain('"path":"\\/elements","value":{}')
        ->toContain('"type":"Stack","props":{}')
        ->toContain('"type":"Separator","props":{}');
});

function connectorComposer(string $visualIdPrefix): ReplyComposer
{
    return resolve(ReplyComposer::class, ['catalogVersion' => 1, 'locale' => 'en', 'timezone' => 'UTC', 'visualIdPrefix' => $visualIdPrefix]);
}

function connectorTextVisual(string $text): string
{
    return "```spec\n{\"op\":\"add\",\"path\":\"/root\",\"value\":\"a\"}\n".json_encode(['op' => 'add', 'path' => '/elements/a', 'value' => ['type' => 'Text', 'props' => ['text' => $text], 'children' => []]])."\n```\n";
}
