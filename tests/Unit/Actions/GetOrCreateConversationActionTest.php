<?php

declare(strict_types=1);

use App\Actions\GetOrCreateConversationAction;
use App\Models\Conversation;
use App\Models\User;

beforeEach(function (): void {
    $this->action = resolve(GetOrCreateConversationAction::class);
});

it('returns existing conversation when it exists', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create([
        'user_id' => $user->id,
        'title' => 'Existing Chat',
    ]);

    $result = $this->action->handle($conversation->id, $user);

    expect($result->id)->toBe($conversation->id);
    expect($result->title)->toBe('Existing Chat');
    expect($result->user_id)->toBe($user->id);
});

it('creates new conversation with default title when it does not exist', function (): void {
    $user = User::factory()->create();
    $conversationId = (string) fake()->uuid();

    $result = $this->action->handle($conversationId, $user);

    expect($result->id)->toBe($conversationId);
    expect($result->user_id)->toBe($user->id);
    expect($result->title)->not->toBeEmpty();

    $this->assertDatabaseHas('agent_conversations', [
        'id' => $conversationId,
        'user_id' => $user->id,
    ]);
});

it('loads messages relationship', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $result = $this->action->handle($conversation->id, $user);

    expect($result->relationLoaded('messages'))->toBeTrue();
});
