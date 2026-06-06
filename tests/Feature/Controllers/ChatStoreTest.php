<?php

declare(strict_types=1);

use App\Http\Controllers\ChatController;
use App\Jobs\ProcessChatStream;
use App\Models\AiUsage;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use App\Services\StreamEventStore;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Messages\MessageRole;

covers(ChatController::class);

beforeEach(function (): void {
    Config::set('plate.enable_premium_upgrades', true);
    Config::set('plate.ai_usage_preflight', [
        'token_budget' => ['input' => 2_000, 'output' => 1_000],
        'fallback_estimate' => 0.01,
    ]);
});

it('starts a conversation from a posted message with an image and redirects to the chat page', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversationId = (string) fake()->uuid();

    $store = $this->mock(StreamEventStore::class);
    $store->shouldReceive('clear')->once()->with($conversationId);

    $this->actingAs($user)
        ->postJson(route('chat.store', $conversationId), [
            'messages' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['type' => 'text', 'text' => 'Analyze this image'],
                        [
                            'type' => 'file',
                            'mediaType' => 'image/png',
                            'url' => 'data:image/png;base64,'.base64_encode('fake-image-bytes'),
                            'filename' => 'meal.png',
                        ],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('chat.create', $conversationId));

    $conversation = Conversation::query()->find($conversationId);

    expect($conversation)->not->toBeNull()
        ->and($conversation->user_id)->toBe($user->id);

    $userMessage = $conversation->messages()
        ->where('role', MessageRole::User)
        ->first();

    expect($userMessage)->not->toBeNull()
        ->and($userMessage->content)->toBe('Analyze this image')
        ->and($userMessage->attachments)->toHaveCount(1)
        ->and($userMessage->attachments[0]['mime'])->toBe('image/png');

    Queue::assertPushed(
        ProcessChatStream::class,
        fn (ProcessChatStream $job): bool => $job->conversationId === $conversationId
            && $job->userId === $user->id
            && $job->content === 'Analyze this image'
            && $job->channel === 'web'
    );
});

it('redirects back with an error and persists nothing when the usage limit is exceeded', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversationId = (string) fake()->uuid();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.396,
    ]);

    $this->from(route('dashboard'))
        ->actingAs($user)
        ->post(route('chat.store', $conversationId), [
            'messages' => [
                ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Over cap']]],
            ],
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors('message');

    Queue::assertNotPushed(ProcessChatStream::class);

    expect(History::query()->count())->toBe(0);
});

it('rejects an invalid conversation id', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('chat.store', 'not-a-uuid'), [
            'messages' => [
                ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hi']]],
            ],
        ])
        ->assertStatus(400);
});

it('forbids starting a turn in another users conversation', function (): void {
    Queue::fake();

    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($intruder)
        ->postJson(route('chat.store', $conversation->id), [
            'messages' => [
                ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hi']]],
            ],
        ])
        ->assertForbidden();

    Queue::assertNotPushed(ProcessChatStream::class);
});
