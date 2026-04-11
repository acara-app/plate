<?php

declare(strict_types=1);

use App\Enums\PostCategory;
use App\Http\Controllers\PublicPostController;
use App\Models\Content;
use Illuminate\Support\Str;

covers(PublicPostController::class);

it('displays the blog index page', function (): void {
    Content::factory()->post()->create(['slug' => 'post-'.Str::uuid()->toString()]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertViewIs('blog.index');
});

it('displays a single blog post', function (): void {
    $post = Content::factory()->post()->create([
        'slug' => 'test-post-'.Str::uuid()->toString(),
    ]);

    $this->get(route('blog.show', $post->slug))
        ->assertOk()
        ->assertViewIs('blog.show');
});

it('returns 404 for non-existent post', function (): void {
    $this->get(route('blog.show', 'non-existent-post'))
        ->assertNotFound();
});

it('returns 404 for unpublished post', function (): void {
    $post = Content::factory()->post()->unpublished()->create([
        'slug' => 'unpublished-post-'.Str::uuid()->toString(),
    ]);

    $this->get(route('blog.show', $post->slug))
        ->assertNotFound();
});

it('filters posts by category', function (): void {
    Content::factory()->post()->create([
        'slug' => 'cat-post-'.Str::uuid()->toString(),
        'category' => PostCategory::Recipes,
    ]);
    Content::factory()->post()->create([
        'slug' => 'other-post-'.Str::uuid()->toString(),
        'category' => PostCategory::Research,
    ]);

    $this->get(route('blog.category', PostCategory::Recipes->value))
        ->assertOk()
        ->assertViewIs('blog.index')
        ->assertViewHas('posts', fn ($posts): bool => $posts->total() === 1);
});

it('returns 404 for invalid category', function (): void {
    $this->get(route('blog.category', 'invalid-category'))
        ->assertNotFound();
});

it('displays posts for a specific locale', function (): void {
    Content::factory()->post()->localized('mn')->create([
        'slug' => 'mn-post-'.Str::uuid()->toString(),
    ]);
    Content::factory()->post()->create([
        'slug' => 'en-post-'.Str::uuid()->toString(),
        'locale' => 'en',
    ]);

    $this->get(route('blog.locale.index', ['locale' => 'mn']))
        ->assertOk()
        ->assertViewHas('posts', fn ($posts): bool => $posts->total() === 1);
});

it('shows translations for a post with translation group', function (): void {
    $group = Str::uuid()->toString();

    $enPost = Content::factory()->post()->localized('en', $group)->create([
        'slug' => 'en-translated-'.Str::uuid()->toString(),
    ]);
    Content::factory()->post()->localized('mn', $group)->create([
        'slug' => 'mn-translated-'.Str::uuid()->toString(),
    ]);

    $this->get(route('blog.show', $enPost->slug))
        ->assertOk()
        ->assertViewHas('translations', fn ($translations): bool => $translations->count() === 1);
});

it('displays a locale-specific blog post via locale route', function (): void {
    $post = Content::factory()->post()->localized('mn')->create([
        'slug' => 'mn-detail-'.Str::uuid()->toString(),
    ]);

    $this->get(route('blog.locale.show', ['locale' => 'mn', 'slug' => $post->slug]))
        ->assertOk()
        ->assertViewIs('blog.show');
});

it('displays category page via locale route', function (): void {
    Content::factory()->post()->localized('fr')->create([
        'slug' => 'fr-cat-'.Str::uuid()->toString(),
        'category' => PostCategory::Lifestyle,
    ]);

    $this->get(route('blog.locale.category', ['locale' => 'fr', 'category' => PostCategory::Lifestyle->value]))
        ->assertOk()
        ->assertViewIs('blog.index');
});

it('resolves category as PostCategory enum for post content', function (): void {
    $post = Content::factory()->post()->create([
        'slug' => 'enum-test-'.Str::uuid()->toString(),
        'category' => PostCategory::NutritionTips,
    ]);

    expect($post->fresh()->category)->toBe(PostCategory::NutritionTips);
});

it('includes canonical url with page parameter for paginated index', function (): void {
    foreach (range(1, 15) as $i) {
        Content::factory()->post()->create([
            'slug' => 'post-page-'.Str::uuid()->toString(),
        ]);
    }

    $response = $this->get(route('blog.index', ['page' => 2]));

    $response->assertOk()
        ->assertViewIs('blog.index')
        ->assertViewHas('canonicalUrl');
});

it('includes canonical url with page parameter for locale paginated index', function (): void {
    foreach (range(1, 15) as $i) {
        Content::factory()->post()->localized('mn')->create([
            'slug' => 'mn-post-page-'.Str::uuid()->toString(),
        ]);
    }

    $response = $this->get(route('blog.locale.index', ['locale' => 'mn', 'page' => 2]));

    $response->assertOk()
        ->assertViewIs('blog.index')
        ->assertViewHas('canonicalUrl');
});
