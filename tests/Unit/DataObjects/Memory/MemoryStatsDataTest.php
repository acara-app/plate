<?php

declare(strict_types=1);

use App\DataObjects\Memory\MemoryStatsData;

it('can be created from array using from method', function (): void {
    $data = [
        'totalMemories' => 150,
        'activeMemories' => 120,
        'archivedMemories' => 30,
        'lastUpdate' => '2024-01-22T15:30:00Z',
        'categoriesCount' => ['preference' => 45, 'fact' => 60, 'instruction' => 25],
        'importanceDistribution' => [1 => 10, 5 => 50, 10 => 20],
        'storageSizeMb' => 12.5,
        'expiringCount' => 5,
    ];

    $stats = MemoryStatsData::from($data);

    expect($stats)
        ->totalMemories->toBe(150)
        ->activeMemories->toBe(120)
        ->archivedMemories->toBe(30)
        ->lastUpdate->toBe('2024-01-22T15:30:00Z')
        ->categoriesCount->toBe(['preference' => 45, 'fact' => 60, 'instruction' => 25])
        ->importanceDistribution->toBe([1 => 10, 5 => 50, 10 => 20])
        ->storageSizeMb->toBe(12.5)
        ->expiringCount->toBe(5);
});

it('can be created directly with constructor', function (): void {
    $stats = new MemoryStatsData(
        totalMemories: 100,
        activeMemories: 80,
        archivedMemories: 20,
        lastUpdate: '2024-01-20T10:00:00Z',
        categoriesCount: ['health' => 30],
        importanceDistribution: [5 => 40],
        storageSizeMb: 8.2,
    );

    expect($stats)
        ->totalMemories->toBe(100)
        ->activeMemories->toBe(80)
        ->archivedMemories->toBe(20)
        ->lastUpdate->toBe('2024-01-20T10:00:00Z')
        ->storageSizeMb->toBe(8.2)
        ->expiringCount->toBe(0);
});

it('can be converted to array', function (): void {
    $stats = new MemoryStatsData(
        totalMemories: 50,
        activeMemories: 50,
        archivedMemories: 0,
        lastUpdate: null,
        categoriesCount: [],
        importanceDistribution: [],
        storageSizeMb: 1.0,
    );

    $array = $stats->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['total_memories', 'active_memories', 'archived_memories', 'last_update', 'storage_size_mb'])
        ->and($array['total_memories'])->toBe(50)
        ->and($array['last_update'])->toBeNull();
});

it('handles nullable lastUpdate for empty stores', function (): void {
    $stats = new MemoryStatsData(
        totalMemories: 0,
        activeMemories: 0,
        archivedMemories: 0,
        lastUpdate: null,
        categoriesCount: [],
        importanceDistribution: [],
        storageSizeMb: 0.0,
    );

    expect($stats->lastUpdate)->toBeNull();
});

it('defaults expiringCount to zero', function (): void {
    $stats = new MemoryStatsData(
        totalMemories: 10,
        activeMemories: 10,
        archivedMemories: 0,
        lastUpdate: '2024-01-01T00:00:00Z',
        categoriesCount: [],
        importanceDistribution: [],
        storageSizeMb: 0.5,
    );

    expect($stats->expiringCount)->toBe(0);
});
