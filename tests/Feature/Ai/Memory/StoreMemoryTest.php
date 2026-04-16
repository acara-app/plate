<?php

declare(strict_types=1);

use App\Ai\Exceptions\Memory\MemoryStorageException;
use App\Ai\Facades\Memory;
use App\Enums\MemoryType;
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

it('stores a memory for the authenticated user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $id = Memory::store('I prefer oat milk in my coffee.');

    expect($id)->toBeString();

    $row = MemoryModel::query()->findOrFail($id);
    expect($row->user_id)->toBe($user->id)
        ->and($row->content)->toBe('I prefer oat milk in my coffee.')
        ->and($row->embedding)->toBeString()
        ->and($row->getEmbeddingArray())->toHaveCount(8);
});

it('accepts a pre-computed vector and stores it verbatim', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $vector = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8];

    $id = Memory::store('custom', [], $vector);

    $row = MemoryModel::query()->findOrFail($id);
    expect($row->getEmbeddingArray())->toBe($vector);
});

it('clamps importance to configured min/max', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $tooLow = Memory::store('a', [], null, -5);
    $tooHigh = Memory::store('b', [], null, 999);

    expect(MemoryModel::query()->findOrFail($tooLow)->importance)->toBe(1)
        ->and(MemoryModel::query()->findOrFail($tooHigh)->importance)->toBe(10);
});

it('stores metadata, categories and expiration', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $expires = now()->addDays(7);

    $id = Memory::store(
        content: 'Family dinner on Sundays',
        metadata: ['topic' => 'family'],
        vector: null,
        importance: 6,
        categories: ['family', 'routine'],
        expiresAt: $expires,
    );

    $row = MemoryModel::query()->findOrFail($id);
    expect($row->metadata)->toMatchArray(['topic' => 'family'])
        ->and($row->categories)->toBe(['family', 'routine'])
        ->and($row->importance)->toBe(6)
        ->and($row->expires_at->timestamp)->toBe($expires->timestamp);
});

it('resolves user_id from metadata when no user is authenticated', function (): void {
    $user = User::factory()->create();

    $id = Memory::store('system fact', ['user_id' => $user->id]);

    expect(MemoryModel::query()->findOrFail($id)->user_id)->toBe($user->id);
});

it('throws MemoryStorageException when no user can be resolved', function (): void {
    Memory::store('orphan');
})->throws(MemoryStorageException::class);

it('persists a valid memory_type and falls back to null for invalid values', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $validId = Memory::store('milk allergy', [], null, 5, [], null, MemoryType::Preference->value);
    $invalidId = Memory::store('misc note', [], null, 5, [], null, 'not-a-real-type');
    $nullId = Memory::store('plain', [], null, 5, []);

    expect(MemoryModel::query()->findOrFail($validId)->memory_type)->toBe(MemoryType::Preference)
        ->and(MemoryModel::query()->findOrFail($invalidId)->memory_type)->toBeNull()
        ->and(MemoryModel::query()->findOrFail($nullId)->memory_type)->toBeNull();
});
