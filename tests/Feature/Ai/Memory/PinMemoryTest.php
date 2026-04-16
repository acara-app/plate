<?php

declare(strict_types=1);

use App\Ai\Facades\Memory;
use App\Models\Memory as MemoryModel;
use App\Models\User;
use App\Services\Memory\MemoryConsolidator;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);

    Embeddings::fake(fn (EmbeddingsPrompt $prompt): array => array_map(
        fn (): array => Embeddings::fakeEmbedding($prompt->dimensions ?? 8),
        $prompt->inputs,
    ));
});

it('pins a memory via the update tool', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $id = Memory::store('User has celiac disease', [], null, 10);

    Memory::update($id, null, null, null, true);

    expect(MemoryModel::query()->findOrFail($id)->is_pinned)->toBeTrue();

    Memory::update($id, null, null, null, false);
    expect(MemoryModel::query()->findOrFail($id)->is_pinned)->toBeFalse();
});

it('skips pinned memories when decaying', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $pinned = MemoryModel::factory()->for($user)->create([
        'is_pinned' => true,
        'importance' => 8,
        'created_at' => now()->subDays(60),
    ]);

    $unpinned = MemoryModel::factory()->for($user)->create([
        'is_pinned' => false,
        'importance' => 8,
        'created_at' => now()->subDays(60),
    ]);

    $result = Memory::decay(ageThresholdDays: 30, decayFactor: 0.5, minImportance: 1, archiveDecayed: false);

    expect($result['decayed_count'])->toBe(1)
        ->and($pinned->refresh()->importance)->toBe(8)
        ->and($unpinned->refresh()->importance)->toBeLessThan(8);
});

it('excludes pinned memories from consolidation candidates', function (): void {
    $user = User::factory()->create();

    $consolidator = resolve(MemoryConsolidator::class);

    MemoryModel::factory()->for($user)
        ->withVector([1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0])
        ->create(['is_pinned' => true, 'content' => 'pinned truth']);

    $candidates = $consolidator->candidatesFor($user->id);

    expect($candidates->pluck('is_pinned')->all())->not->toContain(true);
});
