<?php

declare(strict_types=1);

use App\Enums\ContentType;
use App\Enums\FoodCategory;
use App\Models\Content;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

it('casts type to ContentType enum', function (): void {
    $content = Content::factory()->create(['slug' => Str::uuid()->toString()]);

    expect($content->type)->toBeInstanceOf(ContentType::class);
});

it('casts category to FoodCategory enum', function (): void {
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'category' => FoodCategory::Fruits,
    ]);

    expect($content->category)->toBe(FoodCategory::Fruits);
});

it('scopes to published content only', function (): void {
    Content::factory()->create(['slug' => Str::uuid()->toString(), 'is_published' => true]);
    Content::factory()->create(['slug' => Str::uuid()->toString(), 'is_published' => false]);

    expect(Content::published()->count())->toBe(1);
});

it('scopes to specific content type', function (): void {
    Content::factory()->create(['slug' => Str::uuid()->toString(), 'type' => ContentType::Food]);

    expect(Content::ofType(ContentType::Food)->count())->toBe(1);
});

it('scopes to food type', function (): void {
    Content::factory()->create(['slug' => Str::uuid()->toString(), 'type' => ContentType::Food]);

    expect(Content::food()->count())->toBe(1);
});

it('scopes to specific category', function (): void {
    Content::factory()->create(['slug' => Str::uuid()->toString(), 'category' => FoodCategory::Fruits]);
    Content::factory()->create(['slug' => Str::uuid()->toString(), 'category' => FoodCategory::Vegetables]);

    expect(Content::inCategory(FoodCategory::Fruits)->count())->toBe(1);
});

it('returns null image url when no image path', function (): void {
    $content = Content::factory()->create(['slug' => Str::uuid()->toString(), 'image_path' => null]);

    expect($content->image_url)->toBeNull();
});

it('returns image url when image path exists', function (): void {
    Storage::fake('s3_public');

    $content = Content::factory()->withImage()->create(['slug' => Str::uuid()->toString()]);

    expect($content->image_url)->toBeString();
});

it('returns display name from body or title', function (): void {
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'title' => 'Test Title',
        'body' => ['display_name' => 'Custom Display Name'],
    ]);

    expect($content->display_name)->toBe('Custom Display Name');
});

it('returns title when no display name in body', function (): void {
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'title' => 'Test Title',
        'body' => [],
    ]);

    expect($content->display_name)->toBe('Test Title');
});

it('returns diabetic insight from body', function (): void {
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'body' => ['diabetic_insight' => 'This is a test insight'],
    ]);

    expect($content->diabetic_insight)->toBe('This is a test insight');
});

it('returns null when no diabetic insight', function (): void {
    $content = Content::factory()->create(['slug' => Str::uuid()->toString(), 'body' => []]);

    expect($content->diabetic_insight)->toBeNull();
});

it('returns nutrition from body', function (): void {
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'body' => ['nutrition' => ['calories' => 100, 'protein' => 20]],
    ]);

    expect($content->nutrition)
        ->toBeArray()
        ->toHaveKey('calories', 100)
        ->toHaveKey('protein', 20);
});

it('returns empty array when no nutrition', function (): void {
    $content = Content::factory()->create(['slug' => Str::uuid()->toString(), 'body' => []]);

    expect($content->nutrition)->toBe([]);
});

it('returns glycemic assessment from body', function (): void {
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'body' => ['glycemic_assessment' => 'low'],
    ]);

    expect($content->glycemic_assessment)->toBe('low');
});

it('returns null when no glycemic assessment', function (): void {
    $content = Content::factory()->create(['slug' => Str::uuid()->toString(), 'body' => []]);

    expect($content->glycemic_assessment)->toBeNull();
});

it('returns glycemic load from body', function (): void {
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'body' => ['glycemic_load' => '15'],
    ]);

    expect($content->glycemic_load)->toBe('15');
});

it('calculates low glycemic load when no stored value', function (): void {
    $content = Content::factory()->create(['slug' => Str::uuid()->toString(), 'body' => []]);

    expect($content->glycemic_load)->toBe('low');
});

it('calculates glycemic load from nutrition and category', function (): void {
    // Category: Fruits (GI ~40)
    // Carbs: 50g, Fiber: 0g -> Net Carbs: 50g
    // GL = (40 * 50) / 100 = 20 -> High (>= 20)
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'category' => FoodCategory::Fruits,
        'body' => [
            'nutrition' => [
                'carbs' => 50,
                'fiber' => 0,
            ],
        ],
    ]);

    expect($content->glycemic_load)->toBe('high');
});

it('returns category label from category enum', function (): void {
    $content = Content::factory()->create(['slug' => Str::uuid()->toString(), 'category' => FoodCategory::Fruits]);

    expect($content->category_label)->toBe('Fruits');
});

it('returns Uncategorized when no category', function (): void {
    $content = Content::factory()->create(['slug' => Str::uuid()->toString(), 'category' => null]);

    expect($content->category_label)->toBe('Uncategorized');
});

it('returns stored glycemic index from body', function (): void {
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'body' => ['glycemic_index' => 45],
    ]);

    expect($content->glycemic_index)->toBe(45);
});

it('returns category average glycemic index when no stored value', function (): void {
    // Fruits category has average GI of 40
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'category' => FoodCategory::Fruits,
        'body' => [],
    ]);

    expect($content->glycemic_index)->toBe(40);
});

it('returns default glycemic index of 50 when no category', function (): void {
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'category' => null,
        'body' => [],
    ]);

    expect($content->glycemic_index)->toBe(50);
});

it('returns stored numeric glycemic load from body', function (): void {
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'body' => ['glycemic_load_numeric' => 12.5],
    ]);

    expect($content->glycemic_load_numeric)->toBe(12.5);
});

it('calculates numeric glycemic load from nutrition and gi', function (): void {
    // Category: Fruits (GI ~40)
    // Carbs: 50g, Fiber: 10g -> Net Carbs: 40g
    // GL = (40 * 40) / 100 = 16 -> rounded to 16.0
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'category' => FoodCategory::Fruits,
        'body' => [
            'nutrition' => [
                'carbs' => 50,
                'fiber' => 10,
            ],
        ],
    ]);

    expect($content->glycemic_load_numeric)->toBe(16.0);
});

it('calculates glycemic load classification from numeric value', function (): void {
    // Low GL: <= 10
    $lowGlContent = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'category' => FoodCategory::Vegetables, // Low GI category
        'body' => [
            'nutrition' => [
                'carbs' => 10,
                'fiber' => 5,
            ],
        ],
    ]);

    expect($lowGlContent->glycemic_load)->toBe('low');

    // Medium GL: 11-19
    $mediumGlContent = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'category' => FoodCategory::GrainsStarches, // High GI category (65)
        'body' => [
            'nutrition' => [
                'carbs' => 25,
                'fiber' => 0,
            ],
        ],
    ]);

    // GL = (65 * 25) / 100 = 16.25 -> Medium
    expect($mediumGlContent->glycemic_load)->toBe('medium');
});
