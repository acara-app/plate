<?php

declare(strict_types=1);

use App\DataObjects\Memory\RelatedMemoryData;

it('can be created from array using from method', function (): void {
    $data = [
        'id' => 'mem_related_1',
        'content' => 'User mentioned preferring Italian food',
        'relationship' => 'related',
        'depth' => 1,
        'metadata' => ['source' => 'conversation', 'date' => '2024-01-15'],
    ];

    $relatedMemory = RelatedMemoryData::from($data);

    expect($relatedMemory)
        ->id->toBe('mem_related_1')
        ->content->toBe('User mentioned preferring Italian food')
        ->relationship->toBe('related')
        ->depth->toBe(1)
        ->metadata->toBe(['source' => 'conversation', 'date' => '2024-01-15']);
});

it('can be created directly with constructor', function (): void {
    $relatedMemory = new RelatedMemoryData(
        id: 'mem_related_2',
        content: 'User dislikes spicy food',
        relationship: 'contradicts',
        depth: 2,
    );

    expect($relatedMemory)
        ->id->toBe('mem_related_2')
        ->content->toBe('User dislikes spicy food')
        ->relationship->toBe('contradicts')
        ->depth->toBe(2)
        ->metadata->toBe([]);
});

it('can be converted to array', function (): void {
    $relatedMemory = new RelatedMemoryData(
        id: 'mem_related_3',
        content: 'Follows from previous discussion',
        relationship: 'follows',
        depth: 1,
        metadata: ['context' => 'meal planning'],
    );

    $array = $relatedMemory->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['id', 'content', 'relationship', 'depth', 'metadata'])
        ->and($array['relationship'])->toBe('follows')
        ->and($array['depth'])->toBe(1);
});

it('defaults metadata to empty array', function (): void {
    $relatedMemory = new RelatedMemoryData(
        id: 'mem_no_meta',
        content: 'Simple memory',
        relationship: 'related',
        depth: 1,
    );

    expect($relatedMemory->metadata)->toBe([]);
});

it('accepts various relationship types', function (string $relationshipType): void {
    $relatedMemory = new RelatedMemoryData(
        id: 'mem_rel_type',
        content: 'Test content',
        relationship: $relationshipType,
        depth: 1,
    );

    expect($relatedMemory->relationship)->toBe($relationshipType);
})->with([
    'related' => 'related',
    'contradicts' => 'contradicts',
    'follows' => 'follows',
    'refines' => 'refines',
    'supersedes' => 'supersedes',
]);

it('supports multiple depth levels', function (int $depth): void {
    $relatedMemory = new RelatedMemoryData(
        id: 'mem_depth',
        content: 'Test content',
        relationship: 'related',
        depth: $depth,
    );

    expect($relatedMemory->depth)->toBe($depth);
})->with([
    'depth 1' => 1,
    'depth 2' => 2,
    'depth 3' => 3,
    'depth 5' => 5,
]);
