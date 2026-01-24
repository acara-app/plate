<?php

declare(strict_types=1);

use App\DataObjects\Memory\MemoryData;

it('can be created from array using from method', function (): void {
    $data = [
        'id' => 'mem_123',
        'content' => 'User prefers morning workouts',
        'metadata' => ['source' => 'chat', 'user_id' => 42],
        'importance' => 8,
        'categories' => ['preference', 'fitness'],
        'createdAt' => '2024-01-15T10:30:00Z',
        'updatedAt' => '2024-01-16T14:00:00Z',
        'expiresAt' => '2025-01-15T10:30:00Z',
        'isArchived' => false,
    ];

    $memoryData = MemoryData::from($data);

    expect($memoryData)
        ->id->toBe('mem_123')
        ->content->toBe('User prefers morning workouts')
        ->metadata->toBe(['source' => 'chat', 'user_id' => 42])
        ->importance->toBe(8)
        ->categories->toBe(['preference', 'fitness'])
        ->createdAt->toBe('2024-01-15T10:30:00Z')
        ->updatedAt->toBe('2024-01-16T14:00:00Z')
        ->expiresAt->toBe('2025-01-15T10:30:00Z')
        ->isArchived->toBeFalse();
});

it('can be created directly with constructor', function (): void {
    $memoryData = new MemoryData(
        id: 'mem_456',
        content: 'User is allergic to peanuts',
        metadata: ['severity' => 'high'],
        importance: 10,
        categories: ['health', 'allergy'],
        createdAt: '2024-01-10T08:00:00Z',
    );

    expect($memoryData)
        ->id->toBe('mem_456')
        ->content->toBe('User is allergic to peanuts')
        ->metadata->toBe(['severity' => 'high'])
        ->importance->toBe(10)
        ->categories->toBe(['health', 'allergy'])
        ->createdAt->toBe('2024-01-10T08:00:00Z')
        ->updatedAt->toBeNull()
        ->expiresAt->toBeNull()
        ->isArchived->toBeFalse();
});

it('can be converted to array', function (): void {
    $memoryData = new MemoryData(
        id: 'mem_789',
        content: 'User works from home on Fridays',
        metadata: ['day' => 'friday'],
        importance: 5,
        categories: ['work', 'schedule'],
        createdAt: '2024-01-20T12:00:00Z',
        isArchived: true,
    );

    $array = $memoryData->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['id', 'content', 'metadata', 'importance', 'categories', 'created_at', 'is_archived'])
        ->and($array['id'])->toBe('mem_789')
        ->and($array['is_archived'])->toBeTrue();
});

it('handles optional fields correctly', function (): void {
    $memoryData = new MemoryData(
        id: 'mem_opt',
        content: 'Test content',
        metadata: [],
        importance: 1,
        categories: [],
        createdAt: '2024-01-01T00:00:00Z',
        updatedAt: '2024-01-02T00:00:00Z',
        expiresAt: '2024-12-31T23:59:59Z',
        isArchived: true,
    );

    expect($memoryData)
        ->updatedAt->toBe('2024-01-02T00:00:00Z')
        ->expiresAt->toBe('2024-12-31T23:59:59Z')
        ->isArchived->toBeTrue();
});
