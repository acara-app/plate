<?php

declare(strict_types=1);

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Facades\Memory;
use App\Data\Memory\RelatedMemoryData;
use App\Models\Memory as MemoryModel;
use App\Models\MemoryLink;
use App\Models\User;

it('creates bidirectional links by default', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    [$a, $b] = MemoryModel::factory()->for($user)->count(2)->create();

    expect(Memory::link([$a->id, $b->id]))->toBeTrue();

    expect(MemoryLink::query()->count())->toBe(2)
        ->and(MemoryLink::query()->where('source_memory_id', $a->id)->where('target_memory_id', $b->id)->exists())->toBeTrue()
        ->and(MemoryLink::query()->where('source_memory_id', $b->id)->where('target_memory_id', $a->id)->exists())->toBeTrue();
});

it('creates one-way links when bidirectional is false', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    [$a, $b] = MemoryModel::factory()->for($user)->count(2)->create();

    Memory::link([$a->id, $b->id], 'follows', bidirectional: false);

    expect(MemoryLink::query()->count())->toBe(1)
        ->and(MemoryLink::query()->first()->relationship)->toBe('follows');
});

it('is idempotent on repeated links', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    [$a, $b] = MemoryModel::factory()->for($user)->count(2)->create();

    Memory::link([$a->id, $b->id]);
    Memory::link([$a->id, $b->id]);

    expect(MemoryLink::query()->count())->toBe(2);
});

it('throws when any memory id is not found', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $memory = MemoryModel::factory()->for($user)->create();

    Memory::link([$memory->id, '01JABCDEFGHJKMNPQRSTVWXYZ0']);
})->throws(MemoryNotFoundException::class);

it('traverses related memories up to depth', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    [$a, $b, $c] = MemoryModel::factory()->for($user)->count(3)->create();

    Memory::link([$a->id, $b->id], bidirectional: false);
    Memory::link([$b->id, $c->id], bidirectional: false);

    $depthOne = Memory::getRelated($a->id, depth: 1);
    $depthTwo = Memory::getRelated($a->id, depth: 2);

    expect($depthOne)->toHaveCount(1)
        ->and($depthOne[0]->id)->toBe($b->id)
        ->and($depthOne[0]->depth)->toBe(1);

    expect($depthTwo)->toHaveCount(2);
    $ids = array_map(fn (RelatedMemoryData $r): string => $r->id, $depthTwo);
    expect($ids)->toContain($b->id, $c->id);
});
