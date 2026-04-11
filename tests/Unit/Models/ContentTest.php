<?php

declare(strict_types=1);

use App\DataObjects\ContentMetaData;
use App\Enums\ContentType;
use App\Enums\FoodCategory;
use App\Enums\PostCategory;
use App\Models\Content;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

covers(Content::class);

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

it('returns null meta when meta_data is null', function (): void {
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'meta_data' => null,
    ]);

    expect($content->meta)->toBeNull()
        ->and($content->meta_title)->toBe('')
        ->and($content->meta_description)->toBe('')
        ->and($content->manual_links)->toBe([]);
});

it('returns seo metadata attributes from meta data', function (): void {
    $content = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'meta_data' => [
            'seo_title' => 'Banana and blood sugar',
            'seo_description' => 'A quick glycemic overview for bananas.',
            'manual_links' => [
                ['slug' => 'banana', 'anchor' => 'Banana guide'],
            ],
        ],
    ]);

    expect($content->meta)->toBeInstanceOf(ContentMetaData::class)
        ->and($content->meta?->seoTitle)->toBe('Banana and blood sugar')
        ->and($content->meta?->seoDescription)->toBe('A quick glycemic overview for bananas.')
        ->and($content->meta_title)->toBe('Banana and blood sugar')
        ->and($content->meta_description)->toBe('A quick glycemic overview for bananas.')
        ->and($content->manual_links)->toBe([
            ['slug' => 'banana', 'anchor' => 'Banana guide'],
        ]);
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
    $lowGlContent = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'category' => FoodCategory::Vegetables,
        'body' => [
            'nutrition' => [
                'carbs' => 10,
                'fiber' => 5,
            ],
        ],
    ]);

    expect($lowGlContent->glycemic_load)->toBe('low');

    $mediumGlContent = Content::factory()->create([
        'slug' => Str::uuid()->toString(),
        'category' => FoodCategory::GrainsStarches,
        'body' => [
            'nutrition' => [
                'carbs' => 25,
                'fiber' => 0,
            ],
        ],
    ]);

    expect($mediumGlContent->glycemic_load)->toBe('medium');
});

it('resolves category as PostCategory for post content', function (): void {
    $content = Content::factory()->post()->create([
        'slug' => Str::uuid()->toString(),
        'category' => PostCategory::NutritionTips,
    ]);

    expect($content->category)->toBe(PostCategory::NutritionTips)
        ->and($content->category_label)->toBe('Nutrition Tips');
});

it('resolves category as null for post without category', function (): void {
    $content = Content::factory()->post()->create([
        'slug' => Str::uuid()->toString(),
        'category' => null,
    ]);

    expect($content->category)->toBeNull()
        ->and($content->category_label)->toBe('Uncategorized');
});

it('scopes to post type', function (): void {
    Content::factory()->post()->create(['slug' => Str::uuid()->toString()]);
    Content::factory()->create(['slug' => Str::uuid()->toString()]);

    expect(Content::post()->count())->toBe(1);
});

it('scopes to specific locale', function (): void {
    Content::factory()->post()->localized('mn')->create(['slug' => Str::uuid()->toString()]);
    Content::factory()->post()->create(['slug' => Str::uuid()->toString(), 'locale' => 'en']);

    expect(Content::inLocale('mn')->count())->toBe(1);
});

it('scopes post category using inCategory', function (): void {
    Content::factory()->post()->create([
        'slug' => Str::uuid()->toString(),
        'category' => PostCategory::Recipes,
    ]);
    Content::factory()->post()->create([
        'slug' => Str::uuid()->toString(),
        'category' => PostCategory::Research,
    ]);

    expect(Content::inCategory(PostCategory::Recipes)->count())->toBe(1);
});

it('returns translations for content with translation group', function (): void {
    $group = Str::uuid()->toString();

    $enPost = Content::factory()->post()->localized('en', $group)->create([
        'slug' => 'en-'.Str::uuid()->toString(),
    ]);
    Content::factory()->post()->localized('mn', $group)->create([
        'slug' => 'mn-'.Str::uuid()->toString(),
    ]);

    expect($enPost->translations)->toHaveCount(2)
        ->and($enPost->translations->where('id', '!=', $enPost->id))->toHaveCount(1);
});

it('returns empty translations for content without translation group', function (): void {
    $post = Content::factory()->post()->create([
        'slug' => Str::uuid()->toString(),
        'translation_group' => null,
    ]);

    expect($post->translations)->toHaveCount(0);
});
