<?php

declare(strict_types=1);

use App\DataObjects\Memory\MemorySearchResultData;

it('can be created from array using from method', function (): void {
    $data = [
        'id' => 'mem_search_1',
        'content' => 'User enjoys hiking on weekends',
        'score' => 0.92,
        'metadata' => ['source' => 'conversation'],
        'importance' => 7,
        'categories' => ['hobby', 'outdoor'],
    ];

    $searchResult = MemorySearchResultData::from($data);

    expect($searchResult)
        ->id->toBe('mem_search_1')
        ->content->toBe('User enjoys hiking on weekends')
        ->score->toBe(0.92)
        ->metadata->toBe(['source' => 'conversation'])
        ->importance->toBe(7)
        ->categories->toBe(['hobby', 'outdoor']);
});

it('can be created directly with constructor', function (): void {
    $searchResult = new MemorySearchResultData(
        id: 'mem_search_2',
        content: 'Prefers coffee over tea',
        score: 0.85,
        metadata: ['confidence' => 'high'],
        importance: 3,
    );

    expect($searchResult)
        ->id->toBe('mem_search_2')
        ->content->toBe('Prefers coffee over tea')
        ->score->toBe(0.85)
        ->metadata->toBe(['confidence' => 'high'])
        ->importance->toBe(3)
        ->categories->toBe([]);
});

it('can be converted to array', function (): void {
    $searchResult = new MemorySearchResultData(
        id: 'mem_search_3',
        content: 'Works at a tech company',
        score: 0.78,
        metadata: [],
        importance: 5,
        categories: ['work', 'career'],
    );

    $array = $searchResult->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['id', 'content', 'score', 'metadata', 'importance', 'categories'])
        ->and($array['score'])->toBe(0.78)
        ->and($array['categories'])->toBe(['work', 'career']);
});

it('defaults categories to empty array', function (): void {
    $searchResult = new MemorySearchResultData(
        id: 'mem_no_cat',
        content: 'Some content',
        score: 0.5,
        metadata: [],
        importance: 1,
    );

    expect($searchResult->categories)->toBe([]);
});
