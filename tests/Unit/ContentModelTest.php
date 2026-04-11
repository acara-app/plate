<?php

declare(strict_types=1);

use App\Enums\ContentType;
use App\Enums\FoodCategory;
use App\Enums\PostCategory;
use App\Models\Content;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

covers(Content::class);

it('resolves category as FoodCategory for food type', function (): void {
    $content = Content::factory()->create([
        'type' => ContentType::Food,
        'category' => FoodCategory::Fruits,
    ]);

    expect($content->fresh()->category)->toBe(FoodCategory::Fruits);
});

it('resolves category as PostCategory for post type', function (): void {
    $content = Content::factory()->post()->create([
        'category' => PostCategory::NutritionTips,
    ]);

    expect($content->fresh()->category)->toBe(PostCategory::NutritionTips);
});

it('returns null category when value is null', function (): void {
    $content = Content::factory()->create([
        'type' => ContentType::Food,
        'category' => null,
    ]);

    expect($content->fresh()->category)->toBeNull();
});

it('sets category from backed enum value', function (): void {
    $content = Content::factory()->create([
        'type' => ContentType::Post,
    ]);

    $content->category = PostCategory::Recipes;
    $content->save();

    expect($content->fresh()->category)->toBe(PostCategory::Recipes);
});

it('returns null category for unknown type', function (): void {
    $content = Content::factory()->create([
        'category' => null,
    ]);

    expect($content->fresh()->category)->toBeNull();
});

it('returns image url from s3 when path is relative', function (): void {
    Storage::fake('s3_public');

    $content = Content::factory()->withImage()->create([
        'title' => 'Test Food',
    ]);

    expect($content->image_url)->toContain('food-images/');
});

it('returns null image url when no image path', function (): void {
    $content = Content::factory()->create(['image_path' => null]);

    expect($content->image_url)->toBeNull();
});

it('returns image url as-is when it starts with https', function (): void {
    $content = Content::factory()->create([
        'image_path' => 'https://example.com/image.jpg',
    ]);

    expect($content->image_url)->toBe('https://example.com/image.jpg');
});

it('returns meta attribute with seo data', function (): void {
    $content = Content::factory()->create([
        'meta_data' => [
            'seo_title' => 'Test SEO Title',
            'seo_description' => 'Test SEO Description',
            'manual_links' => [],
        ],
    ]);

    expect($content->meta->seoTitle)->toBe('Test SEO Title')
        ->and($content->meta->seoDescription)->toBe('Test SEO Description');
});

it('returns null meta when meta_data is null', function (): void {
    $content = Content::factory()->create(['meta_data' => null]);

    expect($content->meta)->toBeNull();
});

it('returns empty meta title when meta is null', function (): void {
    $content = Content::factory()->create(['meta_data' => null]);

    expect($content->meta_title)->toBe('');
});

it('returns empty meta description when meta is null', function (): void {
    $content = Content::factory()->create(['meta_data' => null]);

    expect($content->meta_description)->toBe('');
});

it('returns display name from body', function (): void {
    $content = Content::factory()->create([
        'title' => 'Original Title',
        'body' => ['display_name' => 'Display Name'],
    ]);

    expect($content->display_name)->toBe('Display Name');
});

it('falls back to title when display name is missing', function (): void {
    $content = Content::factory()->create([
        'title' => 'Original Title',
        'body' => [],
    ]);

    expect($content->display_name)->toBe('Original Title');
});

it('returns diabetic insight from body', function (): void {
    $content = Content::factory()->create([
        'body' => ['diabetic_insight' => 'Some insight text'],
    ]);

    expect($content->diabetic_insight)->toBe('Some insight text');
});

it('returns null diabetic insight when not set', function (): void {
    $content = Content::factory()->create(['body' => []]);

    expect($content->diabetic_insight)->toBeNull();
});

it('returns nutrition from body', function (): void {
    $nutrition = ['calories' => 100, 'protein' => 5];
    $content = Content::factory()->create([
        'body' => ['nutrition' => $nutrition],
    ]);

    expect($content->nutrition)->toBe($nutrition);
});

it('returns empty nutrition when not set', function (): void {
    $content = Content::factory()->create(['body' => []]);

    expect($content->nutrition)->toBe([]);
});

it('returns glycemic assessment from body', function (): void {
    $content = Content::factory()->create([
        'body' => ['glycemic_assessment' => 'low'],
    ]);

    expect($content->glycemic_assessment)->toBe('low');
});

it('returns null glycemic assessment when not set', function (): void {
    $content = Content::factory()->create(['body' => []]);

    expect($content->glycemic_assessment)->toBeNull();
});

it('returns stored glycemic index when available', function (): void {
    $content = Content::factory()->create([
        'body' => ['glycemic_index' => 55],
    ]);

    expect($content->glycemic_index)->toBe(55);
});

it('falls back to category average glycemic index when not stored', function (): void {
    $content = Content::factory()->create([
        'category' => FoodCategory::Fruits,
        'body' => [],
    ]);

    expect($content->glycemic_index)->toBe(FoodCategory::Fruits->averageGlycemicIndex());
});

it('falls back to 50 when no category and no stored glycemic index', function (): void {
    $content = Content::factory()->create([
        'category' => null,
        'body' => [],
    ]);

    expect($content->glycemic_index)->toBe(50);
});

it('returns stored glycemic load string when available', function (): void {
    $content = Content::factory()->create([
        'body' => ['glycemic_load' => 'low'],
    ]);

    expect($content->glycemic_load)->toBe('low');
});

it('computes low glycemic load from numeric value', function (): void {
    $content = Content::factory()->create([
        'body' => [
            'glycemic_load_numeric' => 5,
            'nutrition' => ['carbs' => 10, 'fiber' => 2],
        ],
    ]);

    expect($content->glycemic_load)->toBe('low');
});

it('computes medium glycemic load from numeric value', function (): void {
    $content = Content::factory()->create([
        'body' => [
            'glycemic_load_numeric' => 15,
            'nutrition' => ['carbs' => 10, 'fiber' => 2],
        ],
    ]);

    expect($content->glycemic_load)->toBe('medium');
});

it('computes high glycemic load from numeric value', function (): void {
    $content = Content::factory()->create([
        'body' => [
            'glycemic_load_numeric' => 25,
            'nutrition' => ['carbs' => 10, 'fiber' => 2],
        ],
    ]);

    expect($content->glycemic_load)->toBe('high');
});

it('returns stored glycemic load numeric when available', function (): void {
    $content = Content::factory()->create([
        'body' => ['glycemic_load_numeric' => 12.5],
    ]);

    expect($content->glycemic_load_numeric)->toBe(12.5);
});

it('calculates glycemic load numeric from nutrition', function (): void {
    $content = Content::factory()->create([
        'body' => [
            'nutrition' => ['carbs' => 20, 'fiber' => 3],
        ],
        'category' => null,
    ]);

    $netCarbs = 20 - 3;
    $expected = round((50 * $netCarbs) / 100, 1);

    expect($content->glycemic_load_numeric)->toBe($expected);
});

it('returns category label for category', function (): void {
    $content = Content::factory()->post()->create([
        'category' => PostCategory::NutritionTips,
    ]);

    expect($content->category_label)->toBe('Nutrition Tips');
});

it('returns uncategorized label when no category', function (): void {
    $content = Content::factory()->create([
        'category' => null,
    ]);

    expect($content->category_label)->toBe('Uncategorized');
});

it('returns manual links from meta data', function (): void {
    $content = Content::factory()->create([
        'meta_data' => [
            'manual_links' => [
                ['slug' => 'banana', 'anchor' => 'Banana Info'],
            ],
        ],
    ]);

    expect($content->manual_links)->toHaveCount(1)
        ->and($content->manual_links[0])->toBe(['slug' => 'banana', 'anchor' => 'Banana Info']);
});

it('returns empty manual links when meta data is null', function (): void {
    $content = Content::factory()->create(['meta_data' => null]);

    expect($content->manual_links)->toBe([]);
});

it('returns meta title when meta data exists', function (): void {
    $content = Content::factory()->create([
        'meta_data' => ['seo_title' => 'My SEO Title'],
    ]);

    expect($content->meta_title)->toBe('My SEO Title');
});

it('returns meta description when meta data exists', function (): void {
    $content = Content::factory()->create([
        'meta_data' => ['seo_description' => 'My SEO Description'],
    ]);

    expect($content->meta_description)->toBe('My SEO Description');
});

it('filters published content with scope', function (): void {
    Content::factory()->create(['slug' => 'published-'.Str::uuid()->toString(), 'is_published' => true]);
    Content::factory()->create(['slug' => 'unpublished-'.Str::uuid()->toString(), 'is_published' => false]);

    expect(Content::query()->published()->count())->toBe(1);
});

it('filters food content with scope', function (): void {
    Content::factory()->create(['type' => ContentType::Food]);
    Content::factory()->post()->create();

    expect(Content::query()->food()->count())->toBe(1);
});

it('filters post content with scope', function (): void {
    Content::factory()->post()->create();
    Content::factory()->create(['type' => ContentType::Food]);

    expect(Content::query()->post()->count())->toBe(1);
});

it('filters content by category with scope', function (): void {
    Content::factory()->create(['slug' => 'apple', 'type' => ContentType::Food, 'category' => FoodCategory::Fruits]);
    Content::factory()->create(['slug' => 'broccoli', 'type' => ContentType::Food, 'category' => FoodCategory::Vegetables]);

    expect(Content::query()->inCategory(FoodCategory::Fruits)->count())->toBe(1);
});

it('filters content by locale with scope', function (): void {
    Content::factory()->create(['locale' => 'en']);
    Content::factory()->create(['locale' => 'mn']);

    expect(Content::query()->inLocale('en')->count())->toBe(1);
});

it('has translations relationship', function (): void {
    $group = Str::uuid()->toString();

    $en = Content::factory()->localized('en', $group)->create();
    $mn = Content::factory()->localized('mn', $group)->create();

    expect($en->fresh()->translations)->toHaveCount(2)
        ->and($en->fresh()->translations->pluck('id'))->toContain($mn->id);
});
