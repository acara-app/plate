<?php

declare(strict_types=1);

use App\Enums\AgentMode;
use App\Http\Controllers\ChatController;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;

use function Pest\Laravel\actingAs;

covers(ChatController::class);

beforeEach(function (): void {
    //
});

it('renders chat page with correct props when no conversation id provided', function (): void {
    $user = User::factory()->create();
    $conversationId = (string) fake()->uuid();

    actingAs($user)
        ->get(route('chat.create', ['conversationId' => $conversationId, 'mode' => AgentMode::Ask->value]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('conversationId', $conversationId)
            ->has('messages', 0)
            ->where('mode', AgentMode::Ask)
        );
});

it('renders chat page with correct props with conversation id', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);
    $history = History::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'Hello',
    ]);

    actingAs($user)
        ->get(route('chat.create', ['conversationId' => $conversation->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('conversationId', $conversation->id)
            ->has('messages', 1)
            ->where('messages.0.id', $history->id)
            ->where('messages.0.role', 'user')
            ->where('messages.0.parts.0.text', 'Hello')
        );
});

it('returns 400 for invalid UUID format', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('chat.create', ['conversationId' => 'not-a-uuid']))
        ->assertStatus(400);
});

it('prevents access to another users conversation', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $owner->id]);

    actingAs($intruder)
        ->get(route('chat.create', ['conversationId' => $conversation->id]))
        ->assertForbidden();
});

it('validates stream endpoint', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->post(route('chat.stream', $conversation->id), [])
        ->assertSessionHasErrors(['messages', 'mode'])
        ->assertSessionDoesntHaveErrors(['model']);
});

it('accepts valid stream request', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->post(route('chat.stream', $conversation->id), [
            'messages' => [
                ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello API']]],
            ],
            'mode' => AgentMode::Ask->value,
        ])
        ->assertOk();
});

it('prevents cross-user access on the stream endpoint', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $owner->id]);

    actingAs($intruder)
        ->post(route('chat.stream', $conversation->id), [
            'messages' => [
                ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Leak me their history']]],
            ],
            'mode' => AgentMode::Ask->value,
        ])
        ->assertForbidden();
});

it('includes image attachments in message parts when loading conversation', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $base64Content = base64_encode('fake-image-data');
    $history = History::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'What is this food?',
        'attachments' => [
            ['type' => 'base64-image', 'name' => null, 'base64' => $base64Content, 'mime' => 'image/jpeg'],
        ],
    ]);

    actingAs($user)
        ->get(route('chat.create', ['conversationId' => $conversation->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('conversationId', $conversation->id)
            ->has('messages', 1)
            ->where('messages.0.id', $history->id)
            ->where('messages.0.parts.0.type', 'text')
            ->where('messages.0.parts.0.text', 'What is this food?')
            ->where('messages.0.parts.1.type', 'file')
            ->where('messages.0.parts.1.mediaType', 'image/jpeg')
            ->where('messages.0.parts.1.url', 'data:image/jpeg;base64,'.$base64Content)
        );
});
