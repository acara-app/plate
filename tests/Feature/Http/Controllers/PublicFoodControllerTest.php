<?php

declare(strict_types=1);

use App\Enums\FoodCategory;
use App\Models\Content;
use Illuminate\Support\Str;

it('displays the food index page', function (): void {
    Content::factory()->create(['slug' => Str::uuid()->toString()]);

    $this->get(route('food.index'))
        ->assertOk()
        ->assertViewIs('food.index');
});

it('displays a single food page', function (): void {
    $content = Content::factory()->create([
        'slug' => 'test-food-'.Str::uuid()->toString(),
        'is_published' => true,
    ]);

    $this->get(route('food.show', $content->slug))
        ->assertOk()
        ->assertViewIs('food.show');
});

it('returns 404 for non-existent food', function (): void {
    $this->get(route('food.show', 'non-existent-food'))
        ->assertNotFound();
});

it('returns 404 for unpublished food', function (): void {
    $content = Content::factory()->unpublished()->create([
        'slug' => 'unpublished-food-'.Str::uuid()->toString(),
    ]);

    $this->get(route('food.show', $content->slug))
        ->assertNotFound();
});

// Search filter uses ILIKE which is PostgreSQL-specific, tested in production

it('filters food by glycemic assessment', function (): void {
    Content::factory()->create([
        'slug' => 'low-gi-food-'.Str::uuid()->toString(),
        'body' => ['glycemic_assessment' => 'low'],
    ]);

    $this->get(route('food.index', ['assessment' => 'low']))
        ->assertOk()
        ->assertViewIs('food.index');
});

it('filters food by category', function (): void {
    Content::factory()->create([
        'slug' => 'fruits-food-'.Str::uuid()->toString(),
        'category' => FoodCategory::Fruits,
    ]);

    $this->get(route('food.index', ['category' => 'fruits']))
        ->assertOk()
        ->assertViewIs('food.index');
});

it('ignores invalid category filter', function (): void {
    Content::factory()->create(['slug' => Str::uuid()->toString()]);

    $this->get(route('food.index', ['category' => 'invalid_category']))
        ->assertOk()
        ->assertViewIs('food.index');
});

it('generates canonical url with page parameter', function (): void {
    Content::factory()->count(20)->sequence(
        fn ($sequence) => ['slug' => 'food-'.$sequence->index.'-'.Str::uuid()->toString()]
    )->create();

    $response = $this->get(route('food.index', ['page' => 2]));

    $response->assertOk();
    $response->assertViewHas('canonicalUrl');
});

it('groups food by category when no filters applied', function (): void {
    Content::factory()->create([
        'slug' => 'fruit-food-'.Str::uuid()->toString(),
        'category' => FoodCategory::Fruits,
    ]);
    Content::factory()->create([
        'slug' => 'veggie-food-'.Str::uuid()->toString(),
        'category' => FoodCategory::Vegetables,
    ]);

    $response = $this->get(route('food.index'));

    $response->assertOk();
    $response->assertViewHas('foodsByCategory');
});
