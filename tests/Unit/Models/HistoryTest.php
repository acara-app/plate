<?php

declare(strict_types=1);

use App\Ai\Agents\NutritionAdvisor;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Laravel\Ai\Messages\MessageRole;

it('has correct fillable attributes', function (): void {
    $history = new History();

    expect($history->getGuarded())->toBe([]);
});

it('has correct casts', function (): void {
    $history = new History();
    $casts = $history->casts();

    expect($casts)
        ->toHaveKey('created_at', 'datetime')
        ->toHaveKey('updated_at', 'datetime')
        ->toHaveKey('role', MessageRole::class)
        ->toHaveKey('attachments', 'array')
        ->toHaveKey('tool_calls', 'array')
        ->toHaveKey('tool_results', 'array')
        ->toHaveKey('usage', 'array')
        ->toHaveKey('meta', 'array');
});

it('uses agent_conversation_messages table', function (): void {
    $history = new History();

    expect($history->getTable())->toBe('agent_conversation_messages');
});

it('belongs to a conversation', function (): void {
    $conversation = Conversation::factory()->create();
    $history = History::factory()->forConversation($conversation)->create();

    expect($history->conversation)->toBeInstanceOf(Conversation::class)
        ->and($history->conversation->id)->toBe($conversation->id);
});

it('belongs to a user', function (): void {
    $history = History::factory()->create();

    expect($history->user)->toBeInstanceOf(User::class);
});

it('casts role to MessageRole enum', function (): void {
    $userMessage = History::factory()->userMessage()->create();
    $assistantMessage = History::factory()->assistantMessage()->create();

    expect($userMessage->role)->toBe(MessageRole::User)
        ->and($assistantMessage->role)->toBe(MessageRole::Assistant);
});

it('casts array fields to arrays', function (): void {
    $history = History::factory()->create([
        'attachments' => ['file1.pdf', 'file2.jpg'],
        'tool_calls' => ['tool1', 'tool2'],
        'tool_results' => ['result1'],
        'usage' => ['tokens' => 100],
        'meta' => ['key' => 'value'],
    ]);

    expect($history->attachments)->toBeArray()->toHaveCount(2)
        ->and($history->tool_calls)->toBeArray()->toHaveCount(2)
        ->and($history->tool_results)->toBeArray()->toHaveCount(1)
        ->and($history->usage)->toBeArray()->toHaveKey('tokens')
        ->and($history->meta)->toBeArray()->toHaveKey('key');
});

it('defaults agent to NutritionAdvisor', function (): void {
    $history = History::factory()->create();

    expect($history->agent)->toBe(NutritionAdvisor::class);
});

it('can set custom agent', function (): void {
    $customAgent = 'App\Ai\Agents\CustomAgent';
    $history = History::factory()->withAgent($customAgent)->create();

    expect($history->agent)->toBe($customAgent);
});

it('factory creates user message correctly', function (): void {
    $history = History::factory()->userMessage()->create();

    expect($history->role)->toBe(MessageRole::User)
        ->and($history->tool_calls)->toBeEmpty()
        ->and($history->tool_results)->toBeEmpty()
        ->and($history->usage)->toBeEmpty();
});

it('factory creates assistant message correctly', function (): void {
    $history = History::factory()->assistantMessage()->create();

    expect($history->role)->toBe(MessageRole::Assistant)
        ->and($history->attachments)->toBeEmpty();
});

it('can create history for specific conversation', function (): void {
    $conversation = Conversation::factory()->create();
    $history = History::factory()->forConversation($conversation)->create();

    expect($history->conversation_id)->toBe($conversation->id)
        ->and($history->user_id)->toBe($conversation->user_id);
});

it('can create history for specific user', function (): void {
    $user = User::factory()->create();
    $history = History::factory()->forUser($user)->create();

    expect($history->user_id)->toBe($user->id);
});

it('generates auto-incrementing integer id on creation', function (): void {
    $history = History::factory()->create();

    expect($history->id)->toBeInt()
        ->and($history->id)->toBeGreaterThan(0);
});

it('factory creates valid history', function (): void {
    $history = History::factory()->create();

    expect($history->exists)->toBeTrue()
        ->and($history->content)->toBeString()
        ->and($history->agent)->toBeString()
        ->and($history->conversation_id)->toBeString()
        ->and($history->user_id)->toBeInt();
});

it('stores content correctly', function (): void {
    $content = 'This is a test message content';
    $history = History::factory()->create(['content' => $content]);

    expect($history->content)->toBe($content);
});

it('can have multiple messages in same conversation', function (): void {
    $conversation = Conversation::factory()->create();

    History::factory()->forConversation($conversation)->userMessage()->create();
    History::factory()->forConversation($conversation)->assistantMessage()->create();
    History::factory()->forConversation($conversation)->userMessage()->create();

    expect($conversation->messages)->toHaveCount(3);
});

it('maintains separate histories for different conversations', function (): void {
    $conversation1 = Conversation::factory()->create();
    $conversation2 = Conversation::factory()->create();

    History::factory()->forConversation($conversation1)->create();
    History::factory()->forConversation($conversation1)->create();
    History::factory()->forConversation($conversation2)->create();

    expect($conversation1->messages)->toHaveCount(2)
        ->and($conversation2->messages)->toHaveCount(1);
});
