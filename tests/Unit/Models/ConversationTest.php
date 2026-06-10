<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\ConversationSummary;
use App\Models\History;
use App\Models\User;
use Laravel\Ai\Messages\MessageRole;

covers(Conversation::class);

it('has correct fillable attributes', function (): void {
    $conversation = new Conversation();

    expect($conversation->getGuarded())->toBe([]);
});

it('has correct casts', function (): void {
    $conversation = new Conversation();
    $casts = $conversation->casts();

    expect($casts)
        ->toHaveKey('id', 'string')
        ->toHaveKey('created_at', 'datetime')
        ->toHaveKey('updated_at', 'datetime');
});

it('uses UUID as primary key', function (): void {
    $conversation = Conversation::factory()->create();

    expect($conversation->id)->toBeString()
        ->and($conversation->getKeyType())->toBe('string')
        ->and($conversation->getIncrementing())->toBeFalse();
});

it('uses agent_conversations table', function (): void {
    $conversation = new Conversation();

    expect($conversation->getTable())->toBe('agent_conversations');
});

it('belongs to a user', function (): void {
    $conversation = Conversation::factory()->create();

    expect($conversation->user)->toBeInstanceOf(User::class);
});

it('has many messages in chronological order', function (): void {
    $conversation = Conversation::factory()->create();

    History::factory()->forConversation($conversation)->create(['created_at' => now()->subHours(2)]);
    History::factory()->forConversation($conversation)->create(['created_at' => now()->subHours(1)]);
    History::factory()->forConversation($conversation)->create(['created_at' => now()->subHours(3)]);

    $messages = $conversation->messages;

    expect($messages)->toHaveCount(3)
        ->and($messages->first()->created_at)->toBeLessThan($messages->last()->created_at);
});

it('orders messages with identical timestamps by id, not insertion order', function (): void {
    $conversation = Conversation::factory()->create();
    $sharedTimestamp = now();

    History::factory()->forConversation($conversation)->assistantMessage()->create([
        'id' => '019e8fb9-3a07-73c2-8bd8-04b6a8f3c69d',
        'created_at' => $sharedTimestamp,
        'content' => 'assistant reply',
    ]);

    History::factory()->forConversation($conversation)->userMessage()->create([
        'id' => '019e8fb9-3a03-72c1-bcf8-066faa269289',
        'created_at' => $sharedTimestamp,
        'content' => 'user question',
    ]);

    $messages = $conversation->messages;

    expect($messages->first()->role)->toBe(MessageRole::User)
        ->and($messages->first()->content)->toBe('user question')
        ->and($messages->last()->role)->toBe(MessageRole::Assistant);
});

it('returns empty collection when no messages exist', function (): void {
    $conversation = Conversation::factory()->create();

    expect($conversation->messages)->toBeEmpty();
});

it('can create conversation with specific title', function (): void {
    $conversation = Conversation::factory()->withTitle('Test Conversation Title')->create();

    expect($conversation->title)->toBe('Test Conversation Title');
});

it('can create conversation for specific user', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    expect($conversation->user_id)->toBe($user->id);
});

it('generates UUID on creation', function (): void {
    $conversation = Conversation::factory()->create();

    expect($conversation->id)->toBeString()
        ->and(mb_strlen($conversation->id))->toBe(36);
});

it('factory creates valid conversation', function (): void {
    $conversation = Conversation::factory()->create();

    expect($conversation->exists)->toBeTrue()
        ->and($conversation->title)->toBeString()
        ->and($conversation->user_id)->toBeInt();
});

it('has many summaries', function (): void {
    $conversation = Conversation::factory()->create();

    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 1]);
    ConversationSummary::factory()->create(['conversation_id' => $conversation->id, 'sequence_number' => 2]);

    expect($conversation->summaries)->toHaveCount(2)
        ->and($conversation->summaries->first())->toBeInstanceOf(ConversationSummary::class);
});

it('returns empty collection when no summaries exist', function (): void {
    $conversation = Conversation::factory()->create();

    expect($conversation->summaries)->toBeEmpty();
});

it('detects a pending chat stream among its messages', function (): void {
    $conversation = Conversation::factory()->create();

    History::factory()->forConversation($conversation)->userMessage()->create();

    expect($conversation->hasPendingChatStream())->toBeFalse();

    History::factory()->forConversation($conversation)->assistantMessage()->create([
        'meta' => History::streamMeta('stream-1', History::STREAM_STATUS_PENDING),
    ]);

    expect($conversation->refresh()->hasPendingChatStream())->toBeTrue();
});
