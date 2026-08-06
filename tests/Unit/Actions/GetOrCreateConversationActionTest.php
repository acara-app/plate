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
        ...Conversation::participantAttributes($this->user),
        'title' => 'Existing Chat',
    ]);

    $result = $this->action->handle($conversation->id, $this->user);

    expect($result->id)->toBe($conversation->id)
        ->and($result->title)->toBe('Existing Chat')
        ->and($result->participant_id)->toBe($this->user->id);
});

it('creates new conversation with the default title when it does not exist', function (): void {
    $conversationId = (string) fake()->uuid();

    $result = $this->action->handle($conversationId, $this->user);

    expect($result->id)->toBe($conversationId)
        ->and($result->participant_id)->toBe($this->user->id)
        ->and($result->title)->toBe(Conversation::DEFAULT_TITLE);

    $this->assertDatabaseHas('agent_conversations', [
        'id' => $conversationId,
        'participant_type' => 'user',
        'participant_id' => $this->user->id,
        'title' => Conversation::DEFAULT_TITLE,
    ]);
});

it('loads messages relationship', function (): void {
    $conversation = Conversation::factory()->create(Conversation::participantAttributes($this->user));

    $result = $this->action->handle($conversation->id, $this->user);

    expect($result->relationLoaded('messages'))->toBeTrue();
});

it('skips the messages relationship when not requested', function (): void {
    $conversation = Conversation::factory()->create(Conversation::participantAttributes($this->user));

    $result = $this->action->handle($conversation->id, $this->user, withMessages: false);

    expect($result->relationLoaded('messages'))->toBeFalse();
});

it('returns an existing conversation even when owned by another user (authorization is not this action concern)', function (): void {
    $owner = User::factory()->create();
    $conversation = Conversation::factory()->create(Conversation::participantAttributes($owner));

    $result = $this->action->handle($conversation->id, $this->user);

    expect($result->id)->toBe($conversation->id)
        ->and($result->participant_id)->toBe($owner->id);
});
