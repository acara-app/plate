<?php

declare(strict_types=1);

use App\Ai\AgentBuilder;
use App\Ai\AgentPayload;
use App\Contracts\Memory\ManagesMemoryContext;
use App\Enums\AgentMode;
use App\Models\User;

covers(AgentBuilder::class);

it('does not render the memory storage protocol when memory is disabled', function (): void {
    $user = User::factory()->create();

    $payload = new AgentPayload(
        userId: $user->id,
        message: 'hi',
        mode: AgentMode::Ask,
    );

    $instructions = resolve(AgentBuilder::class)->buildInstructions($payload, $user);

    expect($instructions)
        ->not->toContain('## Memory Storage Protocol')
        ->not->toContain('store_memory');
});

it('renders the memory storage protocol partial when memory is enabled', function (): void {
    app()->bind(ManagesMemoryContext::class, static fn (): ManagesMemoryContext => new class implements ManagesMemoryContext
    {
        /**
         * @param  array<int, array{role: string, content: string}>  $conversationTail
         */
        public function render(int $userId, string $userMessage, array $conversationTail = []): string
        {
            return '';
        }
    });

    $user = User::factory()->create();

    $payload = new AgentPayload(
        userId: $user->id,
        message: 'hi',
        mode: AgentMode::Ask,
    );

    $instructions = resolve(AgentBuilder::class)->buildInstructions($payload, $user);

    expect($instructions)
        ->toContain('## Memory Storage Protocol')
        ->toContain('Always store and pin')
        ->toContain('`is_pinned: true`')
        ->toContain('store_memory');
});
