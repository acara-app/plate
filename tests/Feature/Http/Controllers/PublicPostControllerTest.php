<?php

declare(strict_types=1);

use App\Enums\PostCategory;
use App\Http\Controllers\PublicPostController;
use App\Models\Content;
use Illuminate\Support\Str;

covers(PublicPostController::class);

it('displays the post index page', function (): void {
    Content::factory()->post()->create(['slug' => 'post-'.Str::uuid()->toString()]);

    $this->get(route('post.index'))
        ->assertOk()
        ->assertViewIs('post.index');
});

it('displays a single post', function (): void {
    $post = Content::factory()->post()->create([
        'slug' => 'test-post-'.Str::uuid()->toString(),
    ]);

    $this->get(route('post.show', $post->slug))
        ->assertOk()
        ->assertViewIs('post.show');
});

it('returns 404 for non-existent post', function (): void {
    $this->get(route('post.show', 'non-existent-post'))
        ->assertNotFound();
});

it('returns 404 for unpublished post', function (): void {
    $post = Content::factory()->post()->unpublished()->create([
        'slug' => 'unpublished-post-'.Str::uuid()->toString(),
    ]);

    $this->get(route('post.show', $post->slug))
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

    $this->get(route('post.category', PostCategory::Recipes->value))
        ->assertOk()
        ->assertViewIs('post.index')
        ->assertViewHas('posts', fn ($posts): bool => $posts->total() === 1);
});

it('returns 404 for invalid category', function (): void {
    $this->get(route('post.category', 'invalid-category'))
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

    $this->get(route('post.locale.index', ['locale' => 'mn']))
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

    $this->get(route('post.show', $enPost->slug))
        ->assertOk()
        ->assertViewHas('translations', fn ($translations): bool => $translations->count() === 1);
});

it('displays a locale-specific post via locale route', function (): void {
    $post = Content::factory()->post()->localized('mn')->create([
        'slug' => 'mn-detail-'.Str::uuid()->toString(),
    ]);

    $this->get(route('post.locale.show', ['locale' => 'mn', 'slug' => $post->slug]))
        ->assertOk()
        ->assertViewIs('post.show');
});

it('displays category page via locale route', function (): void {
    Content::factory()->post()->localized('fr')->create([
        'slug' => 'fr-cat-'.Str::uuid()->toString(),
        'category' => PostCategory::Lifestyle,
    ]);

    $this->get(route('post.locale.category', ['locale' => 'fr', 'category' => PostCategory::Lifestyle->value]))
        ->assertOk()
        ->assertViewIs('post.index');
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

    $response = $this->get(route('post.index', ['page' => 2]));

    $response->assertOk()
        ->assertViewIs('post.index')
        ->assertViewHas('canonicalUrl');
});

it('includes canonical url with page parameter for locale paginated index', function (): void {
    foreach (range(1, 15) as $i) {
        Content::factory()->post()->localized('mn')->create([
            'slug' => 'mn-post-page-'.Str::uuid()->toString(),
        ]);
    }

    $response = $this->get(route('post.locale.index', ['locale' => 'mn', 'page' => 2]));

    $response->assertOk()
        ->assertViewIs('post.index')
        ->assertViewHas('canonicalUrl');
});

it('sets app locale for locale-specific index page', function (): void {
    Content::factory()->post()->localized('mn')->create([
        'slug' => 'mn-locale-test-'.Str::uuid()->toString(),
    ]);

    $this->get(route('post.locale.index', ['locale' => 'mn']))
        ->assertOk();

    expect(app()->getLocale())->toBe('mn');
});

it('sets app locale to en for default index page', function (): void {
    $this->get(route('post.index'))->assertOk();

    expect(app()->getLocale())->toBe('en');
});

it('includes hreflang links on index page', function (): void {
    Content::factory()->post()->create([
        'slug' => 'hreflang-test-'.Str::uuid()->toString(),
    ]);

    $this->get(route('post.index'))
        ->assertOk()
        ->assertViewHas('hreflangLinks');
});

it('includes hreflang links on category page', function (): void {
    Content::factory()->post()->create([
        'slug' => 'cat-hreflang-'.Str::uuid()->toString(),
        'category' => PostCategory::Recipes,
    ]);

    $this->get(route('post.category', PostCategory::Recipes->value))
        ->assertOk()
        ->assertViewHas('hreflangLinks');
});

it('falls back x-default to own url when non-english post has no english translation', function (): void {
    $post = Content::factory()->post()->localized('mn')->create([
        'slug' => 'mn-no-en-translation-'.Str::uuid()->toString(),
    ]);

    $postUrl = route('post.locale.show', ['locale' => 'mn', 'slug' => $post->slug]);

    $this->get($postUrl)
        ->assertOk()
        ->assertViewIs('post.show')
        ->assertSee('hreflang="x-default" href="'.$postUrl.'"', false);
});

it('generates correct canonical url for locale category page', function (): void {
    Content::factory()->post()->localized('fr')->create([
        'slug' => 'fr-canonical-'.Str::uuid()->toString(),
        'category' => PostCategory::Lifestyle,
    ]);

    $this->get(route('post.locale.category', ['locale' => 'fr', 'category' => PostCategory::Lifestyle->value]))
        ->assertOk()
        ->assertViewHas('canonicalUrl', route('post.locale.category', ['locale' => 'fr', 'category' => PostCategory::Lifestyle->value]));
});
