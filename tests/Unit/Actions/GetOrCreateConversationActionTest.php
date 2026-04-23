<?php

declare(strict_types=1);

use App\Actions\GetOrCreateConversationAction;
use App\Models\Conversation;
use App\Models\User;

covers(GetOrCreateConversationAction::class);

beforeEach(function (): void {
    $this->action = resolve(GetOrCreateConversationAction::class);
    $this->user = User::factory()->create();
});

it('returns existing conversation when it exists', function (): void {
    $conversation = Conversation::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Existing Chat',
    ]);

    $result = $this->action->handle($conversation->id, $this->user);

    expect($result->id)->toBe($conversation->id)
        ->and($result->title)->toBe('Existing Chat')
        ->and($result->user_id)->toBe($this->user->id);
});

it('creates new conversation with default title when it does not exist', function (): void {
    $conversationId = (string) fake()->uuid();

    $result = $this->action->handle($conversationId, $this->user);

    expect($result->id)->toBe($conversationId)
        ->and($result->user_id)->toBe($this->user->id)
        ->and($result->title)->not->toBeEmpty();

    $this->assertDatabaseHas('agent_conversations', [
        'id' => $conversationId,
        'user_id' => $this->user->id,
    ]);
});

it('loads messages relationship', function (): void {
    $conversation = Conversation::factory()->create(['user_id' => $this->user->id]);

    $result = $this->action->handle($conversation->id, $this->user);

    expect($result->relationLoaded('messages'))->toBeTrue();
});

it('returns an existing conversation even when owned by another user (authorization is not this action concern)', function (): void {
    $owner = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $owner->id]);

    $result = $this->action->handle($conversation->id, $this->user);

    expect($result->id)->toBe($conversation->id)
        ->and($result->user_id)->toBe($owner->id);
});
