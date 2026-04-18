<?php

declare(strict_types=1);

use App\Contracts\Memory\DispatchesMemoryExtraction;
use App\Contracts\Memory\ManagesMemoryContext;
use App\Contracts\Memory\PullsConversationHistory;
use App\Services\Memory\NullConversationHistoryPuller;
use App\Services\Memory\NullMemoryExtractionDispatcher;
use App\Services\Memory\NullMemoryPromptContext;

covers(
    NullConversationHistoryPuller::class,
    NullMemoryExtractionDispatcher::class,
    NullMemoryPromptContext::class,
);

it('renders an empty memory context by default', function (): void {
    $context = new NullMemoryPromptContext;

    expect($context->render(
        userId: 1,
        userMessage: 'What should I eat?',
        conversationTail: [['role' => 'user', 'content' => 'I like tofu.']],
    ))->toBe('');
});

it('ignores memory extraction dispatches by default', function (): void {
    $dispatcher = new NullMemoryExtractionDispatcher;

    $dispatcher->dispatchIfEligible(1);

    expect($dispatcher)->toBeInstanceOf(DispatchesMemoryExtraction::class);
});

it('pulls no conversation history by default', function (): void {
    $puller = new NullConversationHistoryPuller;

    expect($puller->pendingMessagesFor(1, null, null, 10))->toBeEmpty()
        ->and($puller->countPendingFor(1, null, null))->toBe(0);
});

it('resolves public memory contracts from the container', function (): void {
    expect(resolve(ManagesMemoryContext::class))->toBeInstanceOf(ManagesMemoryContext::class)
        ->and(resolve(DispatchesMemoryExtraction::class))->toBeInstanceOf(DispatchesMemoryExtraction::class)
        ->and(resolve(PullsConversationHistory::class))->toBeInstanceOf(PullsConversationHistory::class);
});
