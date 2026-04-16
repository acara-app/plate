<?php

declare(strict_types=1);

use App\Ai\AgentBuilder;
use App\Ai\AgentPayload;
use App\Ai\Tools\Memory\Ai\AiDeleteMemory;
use App\Ai\Tools\Memory\Ai\AiGetImportantMemories;
use App\Ai\Tools\Memory\Ai\AiGetMemory;
use App\Ai\Tools\Memory\Ai\AiLinkMemories;
use App\Ai\Tools\Memory\Ai\AiSearchMemory;
use App\Ai\Tools\Memory\Ai\AiStoreMemory;
use App\Ai\Tools\Memory\Ai\AiUpdateMemory;
use App\Enums\AgentMode;
use App\Models\User;

covers(AgentBuilder::class);

it('exposes the 7 memory adapter tools through AgentBuilder', function (): void {
    $user = User::factory()->create();
    $payload = new AgentPayload(
        userId: $user->id,
        message: 'Hi',
        mode: AgentMode::Ask,
    );

    $result = resolve(AgentBuilder::class)->build($payload, $user);

    $toolClasses = collect($result['tools'])->map(fn (object $t): string => $t::class)->all();

    expect($toolClasses)->toContain(AiStoreMemory::class)
        ->toContain(AiSearchMemory::class)
        ->toContain(AiGetMemory::class)
        ->toContain(AiUpdateMemory::class)
        ->toContain(AiDeleteMemory::class)
        ->toContain(AiGetImportantMemories::class)
        ->toContain(AiLinkMemories::class);
});

it('does not expose internal-only memory operations as AI tools', function (): void {
    $user = User::factory()->create();
    $payload = new AgentPayload(
        userId: $user->id,
        message: 'Hi',
        mode: AgentMode::Ask,
    );

    $result = resolve(AgentBuilder::class)->build($payload, $user);

    $toolNames = collect($result['tools'])
        ->filter(fn (object $t): bool => method_exists($t, 'name'))
        ->map(fn (object $t): string => (string) $t->name())
        ->all();

    expect($toolNames)->not->toContain('consolidate_memories')
        ->not->toContain('decay_memories')
        ->not->toContain('reflect_on_memories')
        ->not->toContain('categorize_memories')
        ->not->toContain('archive_memories')
        ->not->toContain('restore_memories')
        ->not->toContain('validate_memory');
});
