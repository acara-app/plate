<?php

declare(strict_types=1);

use App\Jobs\ProcessChatStream;
use App\Models\AiUsage;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use App\Services\StreamEventStore;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Messages\MessageRole;

beforeEach(function (): void {
    Config::set('plate.enable_premium_upgrades', true);
    Config::set('plate.ai_usage_preflight', [
        'token_budget' => ['input' => 2_000, 'output' => 1_000],
        'fallback_estimate' => 0.01,
    ]);
});

it('dispatches web chat streams to the chat queue and returns immediately', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $store = $this->mock(StreamEventStore::class);
    $store->shouldReceive('clear')->once()->with($conversation->id);

    $this->actingAs($user)
        ->postJson(route('chat.stream', $conversation->id), [
            'messages' => [
                ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello from broadcast']]],
            ],
        ])
        ->assertAccepted()
        ->assertJson([
            'status' => 'processing',
            'channel' => 'chat.'.$user->id,
            'conversationId' => $conversation->id,
        ])
        ->assertJsonStructure([
            'userMessageId',
            'assistantMessageId',
        ]);

    $messages = $conversation->fresh()->messages()->get();
    $userMessage = $messages->first(fn (History $message): bool => $message->role === MessageRole::User);
    $assistantMessage = $messages->first(fn (History $message): bool => $message->role === MessageRole::Assistant);

    expect($messages)->toHaveCount(2)
        ->and($userMessage?->chatStreamStatus())->toBe(History::STREAM_STATUS_SUBMITTED)
        ->and($assistantMessage?->chatStreamStatus())->toBe(History::STREAM_STATUS_PENDING);

    Queue::assertPushed(ProcessChatStream::class, fn (ProcessChatStream $job): bool => $job->queue === 'chat'
        && $job->userId === $user->id
        && $job->conversationId === $conversation->id
        && $job->content === 'Hello from broadcast'
        && $job->streamId !== ''
        && $job->userMessageId === $userMessage?->id
        && $job->assistantMessageId === $assistantMessage?->id
        && $job->channel === 'web');
});

it('keeps usage-limit preflight synchronous for web chat streams', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.396,
    ]);

    $this->actingAs($user)
        ->postJson(route('chat.stream', $conversation->id), [
            'messages' => [
                ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Over cap']]],
            ],
        ])
        ->assertStatus(402)
        ->assertJson([
            'error' => 'usage_limit_exceeded',
            'limit_type' => 'rolling',
            'tier' => 'free',
        ]);

    Queue::assertNotPushed(ProcessChatStream::class);
});

it('dispatches api v2 chat streams with the mobile channel marker', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('mobile:device-1', ['chat:converse'])->plainTextToken;

    $store = $this->mock(StreamEventStore::class);
    $store->shouldReceive('clear')->once()->with($conversation->id);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v2.chat.stream', $conversation->id), [
            'messages' => [
                ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello mobile']]],
            ],
        ])
        ->assertAccepted()
        ->assertJson([
            'status' => 'processing',
            'conversationId' => $conversation->id,
        ])
        ->assertJsonStructure([
            'userMessageId',
            'assistantMessageId',
        ]);

    Queue::assertPushed(ProcessChatStream::class, fn (ProcessChatStream $job): bool => $job->streamId !== ''
        && $job->channel === 'mobile'
        && $job->content === 'Hello mobile');
});

it('requests cancellation for owned web conversations', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $store = $this->mock(StreamEventStore::class);
    $store->shouldReceive('requestCancellation')->once()->with($conversation->id);

    $this->actingAs($user)
        ->postJson(route('chat.stream.stop', $conversation->id))
        ->assertOk()
        ->assertJson([
            'conversationId' => $conversation->id,
        ]);
});

it('returns replay events for owned web conversations', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $store = $this->mock(StreamEventStore::class);
    $store->shouldReceive('eventsAfter')
        ->once()
        ->with($conversation->id, 4)
        ->andReturn([
            ['sequence' => 5, 'type' => 'text_delta', 'data' => ['delta' => 'Hi'], 'vercel' => null],
        ]);
    $store->shouldReceive('isStreaming')->once()->with($conversation->id)->andReturn(true);
    $store->shouldReceive('lastSequence')->once()->with($conversation->id)->andReturn(5);

    $this->actingAs($user)
        ->getJson(route('chat.stream.events', ['conversation' => $conversation->id, 'after' => 4]))
        ->assertOk()
        ->assertJson([
            'streaming' => true,
            'lastSequence' => 5,
            'events' => [
                ['sequence' => 5, 'type' => 'text_delta', 'data' => ['delta' => 'Hi']],
            ],
        ]);
});
