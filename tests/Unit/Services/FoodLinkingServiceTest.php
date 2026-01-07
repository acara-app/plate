<?php

declare(strict_types=1);

use App\Models\Content;
use App\Services\FoodLinkingService;

beforeEach(function (): void {
    FoodLinkingService::clearCache();
});

it('clears the slug cache', function (): void {
    Content::factory()->create(['slug' => 'banana', 'is_published' => true]);

    $service = new FoodLinkingService;
    $service->linkFoodsInText('I love banana');

    FoodLinkingService::clearCache();

    // Test passes if no exception is thrown
    expect(true)->toBeTrue();
});

it('returns original text when no food content exists', function (): void {
    $service = new FoodLinkingService;
    $text = 'I love eating healthy food';

    $result = $service->linkFoodsInText($text);

    expect($result)->toBe($text);
});

it('links matching food names in text', function (): void {
    Content::factory()->create(['slug' => 'banana', 'is_published' => true]);

    $service = new FoodLinkingService;
    $result = $service->linkFoodsInText('I love banana');

    expect($result)->toContain('href=');
    expect($result)->toContain('banana');
});

it('returns empty collection when no food content exists for getFoodsWithPages', function (): void {
    $service = new FoodLinkingService;

    $result = $service->getFoodsWithPages('I love eating healthy food');

    expect($result)->toBeEmpty();
});

it('returns foods with pages that match text', function (): void {
    Content::factory()->create(['slug' => 'apple', 'is_published' => true]);

    $service = new FoodLinkingService;
    $result = $service->getFoodsWithPages('I ate an apple');

    expect($result)->toHaveCount(1);
    expect($result->first()['slug'])->toBe('apple');
});

it('extracts compound food names', function (): void {
    Content::factory()->create(['slug' => 'brown-rice', 'is_published' => true]);

    $service = new FoodLinkingService;
    $result = $service->getFoodsWithPages('I had some brown rice for dinner');

    expect($result)->toHaveCount(1);
    expect($result->first()['slug'])->toBe('brown-rice');
});

it('extracts compound food names for chicken breast', function (): void {
    Content::factory()->create(['slug' => 'chicken-breast', 'is_published' => true]);

    $service = new FoodLinkingService;
    $result = $service->getFoodsWithPages('I grilled chicken breast');

    expect($result)->toHaveCount(1);
    expect($result->first()['slug'])->toBe('chicken-breast');
});

it('extracts compound food names for sweet potato', function (): void {
    Content::factory()->create(['slug' => 'sweet-potato', 'is_published' => true]);

    $service = new FoodLinkingService;
    $result = $service->getFoodsWithPages('I love sweet potato');

    expect($result)->toHaveCount(1);
    expect($result->first()['slug'])->toBe('sweet-potato');
});

it('extracts compound food names for olive oil', function (): void {
    Content::factory()->create(['slug' => 'olive-oil', 'is_published' => true]);

    $service = new FoodLinkingService;
    $result = $service->getFoodsWithPages('Cook with olive oil');

    expect($result)->toHaveCount(1);
    expect($result->first()['slug'])->toBe('olive-oil');
});

it('extracts compound food names for greek yogurt', function (): void {
    Content::factory()->create(['slug' => 'greek-yogurt', 'is_published' => true]);

    $service = new FoodLinkingService;
    $result = $service->getFoodsWithPages('I eat greek yogurt');

    expect($result)->toHaveCount(1);
    expect($result->first()['slug'])->toBe('greek-yogurt');
});

it('returns unique foods when same food appears multiple times', function (): void {
    Content::factory()->create(['slug' => 'banana', 'is_published' => true]);

    $service = new FoodLinkingService;
    $result = $service->getFoodsWithPages('I like banana and banana is good');

    expect($result)->toHaveCount(1);
});

it('ignores unpublished content', function (): void {
    Content::factory()->create(['slug' => 'banana', 'is_published' => false]);

    $service = new FoodLinkingService;
    $result = $service->getFoodsWithPages('I love banana');

    expect($result)->toBeEmpty();
});

it('ignores words shorter than 3 characters', function (): void {
    Content::factory()->create(['slug' => 'ab', 'is_published' => true]);

    $service = new FoodLinkingService;
    $result = $service->getFoodsWithPages('ab cd ef');

    expect($result)->toBeEmpty();
});
