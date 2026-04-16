<?php

declare(strict_types=1);

use App\Ai\Facades\Memory;
use App\Models\Memory as MemoryModel;
use App\Models\User;
use Laravel\Ai\Embeddings;

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);
});

it('returns results ranked by cosine similarity', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $queryVector = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    $highMatch = [0.95, 0.1, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    $lowMatch = [0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

    $high = MemoryModel::factory()->for($user)->withVector($highMatch)->create(['content' => 'high']);
    MemoryModel::factory()->for($user)->withVector($lowMatch)->create(['content' => 'low']);

    Embeddings::fake(fn (): array => [$queryVector]);

    $results = Memory::search('matches high', 5, 0.5);

    expect($results)->toHaveCount(1)
        ->and($results[0]->id)->toBe($high->id)
        ->and($results[0]->score)->toBeGreaterThanOrEqual(0.9);
});

it('scopes search to the authenticated user', function (): void {
    $me = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($me);

    $vector = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

    MemoryModel::factory()->for($me)->withVector($vector)->create(['content' => 'mine']);
    MemoryModel::factory()->for($other)->withVector($vector)->create(['content' => 'theirs']);

    Embeddings::fake(fn (): array => [$vector]);

    $results = Memory::search('anything', 5, 0.1);

    expect($results)->toHaveCount(1)
        ->and($results[0]->content)->toBe('mine');
});

it('excludes archived memories by default', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $vector = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

    MemoryModel::factory()->for($user)->withVector($vector)->archived()->create(['content' => 'old']);

    Embeddings::fake(fn (): array => [$vector]);

    expect(Memory::search('x', 5, 0.1))->toBeEmpty();
    expect(Memory::search('x', 5, 0.1, [], true))->toHaveCount(1);
});

it('records access on every hit', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $vector = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    $memory = MemoryModel::factory()->for($user)->withVector($vector)->create(['access_count' => 3]);

    Embeddings::fake(fn (): array => [$vector]);

    Memory::search('x', 5, 0.1);

    expect($memory->fresh()->access_count)->toBe(4);
});

it('filters by category', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $vector = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

    MemoryModel::factory()->for($user)->withVector($vector)->withCategories(['food'])->create(['content' => 'a']);
    MemoryModel::factory()->for($user)->withVector($vector)->withCategories(['travel'])->create(['content' => 'b']);

    Embeddings::fake(fn (): array => [$vector]);

    $results = Memory::search('x', 5, 0.1, ['category' => 'food']);

    expect($results)->toHaveCount(1)
        ->and($results[0]->content)->toBe('a');
});
