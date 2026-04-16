<?php

declare(strict_types=1);

use App\Ai\Exceptions\Memory\InvalidMemoryFilterException;
use App\Ai\Exceptions\Memory\MemoryNotFoundException;
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

it('gets a memory by id and increments access count', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $memory = MemoryModel::factory()->for($user)->create(['access_count' => 0]);

    $data = Memory::get($memory->id);

    expect($data->id)->toBe($memory->id)
        ->and($data->content)->toBe($memory->content);

    expect($memory->fresh()->access_count)->toBe(1);
});

it('throws MemoryNotFoundException for unknown id', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Memory::get('01JABCDEFGHJKMNPQRSTVWXYZ0');
})->throws(MemoryNotFoundException::class);

it('updates content and regenerates embedding only when content changes', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $memory = MemoryModel::factory()->for($user)->create(['content' => 'old']);
    $originalEmbedding = $memory->embedding;

    Memory::update($memory->id, null, ['extra' => 'data']);
    expect($memory->fresh()->embedding)->toBe($originalEmbedding)
        ->and($memory->fresh()->metadata)->toMatchArray(['extra' => 'data']);

    Memory::update($memory->id, 'new content');
    expect($memory->fresh()->embedding)->not->toBe($originalEmbedding)
        ->and($memory->fresh()->content)->toBe('new content');
});

it('deletes a memory by id', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $memory = MemoryModel::factory()->for($user)->create();

    expect(Memory::delete($memory->id))->toBe(1)
        ->and(MemoryModel::query()->find($memory->id))->toBeNull();
});

it('deletes by filter', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    MemoryModel::factory()->for($user)->count(3)->withCategories(['junk'])->create();
    MemoryModel::factory()->for($user)->withCategories(['keep'])->create();

    $deleted = Memory::delete(null, ['category' => 'junk']);

    expect($deleted)->toBe(3)
        ->and(MemoryModel::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('rejects empty filter deletes', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Memory::delete(null, []);
})->throws(InvalidMemoryFilterException::class);

it('rejects invalid filter fields', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Memory::delete(null, ['bogus' => 'x']);
})->throws(InvalidMemoryFilterException::class);
