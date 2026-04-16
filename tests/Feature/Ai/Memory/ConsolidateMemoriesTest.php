<?php

declare(strict_types=1);

use App\Ai\Exceptions\Memory\MemoryStorageException;
use App\Ai\Facades\Memory;
use App\Models\Memory as MemoryModel;
use App\Models\User;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);

    Embeddings::fake(fn (EmbeddingsPrompt $prompt): array => array_map(
        fn (): array => Embeddings::fakeEmbedding($prompt->dimensions ?? 8),
        $prompt->inputs,
    ));
});

it('creates a consolidated memory and soft-deletes originals', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    [$a, $b] = MemoryModel::factory()->for($user)->count(2)->withImportance(4)->create();

    $newId = Memory::consolidate(
        memoryIds: [$a->id, $b->id],
        synthesizedContent: 'Merged memory',
    );

    $consolidated = MemoryModel::query()->findOrFail($newId);
    expect($consolidated->content)->toBe('Merged memory')
        ->and($consolidated->consolidation_generation)->toBe(1)
        ->and($consolidated->consolidated_from)->toBe([$a->id, $b->id])
        ->and($consolidated->source)->toBe('consolidation');

    expect(MemoryModel::query()->find($a->id))->toBeNull()
        ->and(MemoryModel::query()->find($b->id))->toBeNull();

    expect(MemoryModel::withTrashed()->findOrFail($a->id)->consolidated_into)->toBe($newId)
        ->and(MemoryModel::withTrashed()->findOrFail($b->id)->consolidated_into)->toBe($newId);
});

it('requires at least two memories', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $memory = MemoryModel::factory()->for($user)->create();

    Memory::consolidate([$memory->id], 'one');
})->throws(MemoryStorageException::class);

it('enforces max consolidation generation', function (): void {
    config()->set('memory.consolidation.max_generation', 2);

    $user = User::factory()->create();
    $this->actingAs($user);

    $a = MemoryModel::factory()->for($user)->create(['consolidation_generation' => 2]);
    $b = MemoryModel::factory()->for($user)->create(['consolidation_generation' => 1]);

    Memory::consolidate([$a->id, $b->id], 'gen-capped');
})->throws(MemoryStorageException::class);

it('takes the max importance from source memories when not explicitly provided', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $a = MemoryModel::factory()->for($user)->withImportance(3)->create();
    $b = MemoryModel::factory()->for($user)->withImportance(7)->create();

    $newId = Memory::consolidate([$a->id, $b->id], 'merged');

    expect(MemoryModel::query()->findOrFail($newId)->importance)->toBe(7);
});

it('uses caller-supplied categories instead of merging from sources when provided', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $a = MemoryModel::factory()->for($user)->withCategories(['preference'])->create();
    $b = MemoryModel::factory()->for($user)->withCategories(['habit'])->create();

    $newId = Memory::consolidate(
        memoryIds: [$a->id, $b->id],
        synthesizedContent: 'merged with refined categories',
        categories: ['health', 'allergy'],
    );

    expect(MemoryModel::query()->findOrFail($newId)->categories)->toBe(['health', 'allergy']);
});

it('falls back to unioning source categories when caller passes null', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $a = MemoryModel::factory()->for($user)->withCategories(['preference', 'habit'])->create();
    $b = MemoryModel::factory()->for($user)->withCategories(['habit', 'routine'])->create();

    $newId = Memory::consolidate(
        memoryIds: [$a->id, $b->id],
        synthesizedContent: 'merged without refined categories',
    );

    expect(MemoryModel::query()->findOrFail($newId)->categories)
        ->toEqualCanonicalizing(['preference', 'habit', 'routine']);
});
