<?php

declare(strict_types=1);

use App\Enums\AgentMode;
use App\Enums\ModelName;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    // Setup if needed
});

it('renders chat page with correct props when no conversation id provided', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('chat.create', ['mode' => AgentMode::Ask->value]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('conversationId', null)
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

it('handles invalid conversation id gracefully', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('chat.create', ['conversationId' => 'invalid-id']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('conversationId', null)
            ->has('messages', 0)
        );
});

it('validates stream endpoint', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('chat.stream'), [])
        ->assertSessionHasErrors(['messages', 'mode', 'model']);
});

it('accepts valid stream request', function (): void {
    $user = User::factory()->create();

    $url = route('chat.stream').'?mode='.AgentMode::Ask->value.'&model='.ModelName::GPT_5_MINI->value;

    actingAs($user)
        ->post($url, [
            'messages' => [
                ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello API']]],
            ],
        ])
        ->assertOk();
});
