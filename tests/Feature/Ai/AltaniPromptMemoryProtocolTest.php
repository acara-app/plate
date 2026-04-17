<?php

declare(strict_types=1);

use App\Ai\AgentBuilder;
use App\Ai\AgentPayload;
use App\Enums\AgentMode;
use App\Models\User;

covers(AgentBuilder::class);

it('renders the memory storage protocol partial inside Altani\'s system instructions', function (): void {
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
