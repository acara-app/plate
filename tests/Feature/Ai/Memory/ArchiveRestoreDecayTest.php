<?php

declare(strict_types=1);

use App\Ai\Facades\Memory;
use App\Models\Memory as MemoryModel;
use App\Models\User;

it('archives and restores memories', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $memories = MemoryModel::factory()->for($user)->count(3)->create();
    $ids = $memories->pluck('id')->all();

    expect(Memory::archive($ids))->toBe(3)
        ->and(MemoryModel::query()->whereIn('id', $ids)->where('is_archived', true)->count())->toBe(3);

    expect(Memory::restore($ids))->toBe(3)
        ->and(MemoryModel::query()->whereIn('id', $ids)->where('is_archived', false)->count())->toBe(3);
});

it('decays importance of memories older than the threshold', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $old = MemoryModel::factory()->for($user)->withImportance(10)->create([
        'created_at' => now()->subDays(60),
    ]);
    $fresh = MemoryModel::factory()->for($user)->withImportance(10)->create();

    $stats = Memory::decay(ageThresholdDays: 30, decayFactor: 0.8, minImportance: 1, archiveDecayed: false);

    expect($stats['decayed_count'])->toBe(1)
        ->and($stats['archived_count'])->toBe(0)
        ->and($old->fresh()->importance)->toBe(8)
        ->and($fresh->fresh()->importance)->toBe(10);
});

it('archives memories whose decayed importance falls below min', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $memory = MemoryModel::factory()->for($user)->withImportance(2)->create([
        'created_at' => now()->subDays(60),
    ]);

    $stats = Memory::decay(ageThresholdDays: 30, decayFactor: 0.5, minImportance: 2, archiveDecayed: true);

    expect($stats['archived_count'])->toBe(1)
        ->and($memory->fresh()->is_archived)->toBeTrue()
        ->and($memory->fresh()->importance)->toBe(1);
});

it('returns zeroes when nothing is old enough to decay', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    MemoryModel::factory()->for($user)->count(3)->create();

    $stats = Memory::decay();

    expect($stats['decayed_count'])->toBe(0)
        ->and($stats['avg_importance_before'])->toBe(0.0);
});

it('lists important memories above the threshold', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    MemoryModel::factory()->for($user)->withImportance(9)->create(['content' => 'critical']);
    MemoryModel::factory()->for($user)->withImportance(4)->create(['content' => 'trivial']);

    $important = Memory::getImportant(threshold: 8);

    expect($important)->toHaveCount(1)
        ->and($important[0]->content)->toBe('critical');
});
