<?php

declare(strict_types=1);

use App\Actions\ProcessAdvisorMessageAction;
use App\Ai\Agents\NutritionAdvisor;
use App\Models\User;
use Laravel\Ai\Contracts\ConversationStore;

it('creates new conversation when none exists', function (): void {
    NutritionAdvisor::fake(['Hello!']);

    $conversationStore = mock(ConversationStore::class);
    $conversationStore
        ->shouldReceive('latestConversationId')
        ->with(1)
        ->once()
        ->andReturn(null);

    $conversationStore
        ->shouldReceive('storeConversation')
        ->with(1, 'Telegram Chat')
        ->once()
        ->andReturn('conv-123');

    $action = new ProcessAdvisorMessageAction(
        resolve(NutritionAdvisor::class),
        $conversationStore,
    );

    $user = User::factory()->make(['id' => 1]);
    $result = $action->handle($user, 'Test message');

    expect($result['response'])->toBe('Hello!');
    expect($result['conversation_id'])->toBe('conv-123');
    NutritionAdvisor::assertPrompted('Test message');
});

it('uses existing conversation when provided', function (): void {
    NutritionAdvisor::fake(['Continuing...']);

    $action = new ProcessAdvisorMessageAction(
        resolve(NutritionAdvisor::class),
        resolve(ConversationStore::class),
    );

    $user = User::factory()->make(['id' => 1]);
    $result = $action->handle($user, 'Another message', 'existing-conv');

    expect($result['response'])->toBe('Continuing...');
    expect($result['conversation_id'])->toBe('existing-conv');
});

it('reuses latest conversation when no id provided but exists', function (): void {
    NutritionAdvisor::fake(['Reusing!']);

    $conversationStore = mock(ConversationStore::class);
    $conversationStore
        ->shouldReceive('latestConversationId')
        ->with(1)
        ->once()
        ->andReturn('latest-conv');

    $action = new ProcessAdvisorMessageAction(
        resolve(NutritionAdvisor::class),
        $conversationStore,
    );

    $user = User::factory()->make(['id' => 1]);
    $result = $action->handle($user, 'Message');

    expect($result['response'])->toBe('Reusing!');
    expect($result['conversation_id'])->toBe('latest-conv');
});

it('resets conversation', function (): void {
    $conversationStore = mock(ConversationStore::class);
    $conversationStore
        ->shouldReceive('storeConversation')
        ->with(1, 'Telegram Chat')
        ->once()
        ->andReturn('new-conv');

    $action = new ProcessAdvisorMessageAction(
        resolve(NutritionAdvisor::class),
        $conversationStore,
    );

    $user = User::factory()->make(['id' => 1]);
    $result = $action->resetConversation($user);

    expect($result)->toBe('new-conv');
});
