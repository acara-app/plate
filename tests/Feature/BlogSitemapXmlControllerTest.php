<?php

declare(strict_types=1);

use App\Http\Controllers\BlogSitemapXmlController;
use App\Models\Content;
use Illuminate\Support\Str;

covers(BlogSitemapXmlController::class);

it('returns blog sitemap as xml', function (): void {
    Content::factory()->post()->create([
        'slug' => 'test-blog-post',
        'title' => 'Test Blog Post',
    ]);

    $response = $this->get(route('blog.sitemap'));

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml');

    $content = $response->getContent();
    expect($content)->toContain('<?xml version="1.0"')
        ->toContain('urlset');
});

it('includes blog index pages for all supported locales', function (): void {
    $response = $this->get(route('blog.sitemap'));

    $content = $response->getContent();
    expect($content)->toContain(route('blog.index'));
});

it('includes blog index locale pages for non-en locales', function (): void {
    $response = $this->get(route('blog.sitemap'));

    $content = $response->getContent();
    expect($content)->toContain(route('blog.locale.index', ['locale' => 'mn']))
        ->toContain(route('blog.locale.index', ['locale' => 'fr']));
});

it('only includes published posts', function (): void {
    Content::factory()->post()->create(['slug' => 'published-post']);
    Content::factory()->post()->unpublished()->create(['slug' => 'unpublished-post']);

    $response = $this->get(route('blog.sitemap'));

    $response->assertSuccessful();

    $content = $response->getContent();
    expect($content)->toContain('published-post')
        ->not->toContain('unpublished-post');
});

it('includes post image when available', function (): void {
    Content::factory()->post()->withImage()->create([
        'slug' => 'post-with-image',
    ]);

    $response = $this->get(route('blog.sitemap'));

    $response->assertSuccessful();

    $content = $response->getContent();
    expect($content)->toContain('image:image');
});

it('includes alternate links for translated posts', function (): void {
    $group = Str::uuid()->toString();

    Content::factory()->post()->localized('en', $group)->create([
        'slug' => 'en-translated-post',
    ]);
    Content::factory()->post()->localized('mn', $group)->create([
        'slug' => 'mn-translated-post',
    ]);

    $response = $this->get(route('blog.sitemap'));

    $response->assertSuccessful();

    $content = $response->getContent();
    expect($content)->toContain('x-default');
});

it('generates locale-specific urls for non-en posts', function (): void {
    Content::factory()->post()->localized('mn')->create([
        'slug' => 'mn-blog-post',
    ]);

    $response = $this->get(route('blog.sitemap'));

    $response->assertSuccessful();

    $content = $response->getContent();
    expect($content)->toContain(route('blog.locale.show', ['locale' => 'mn', 'slug' => 'mn-blog-post']));
});

it('returns empty sitemap when no posts exist', function (): void {
    $response = $this->get(route('blog.sitemap'));

    $response->assertSuccessful();

    $content = $response->getContent();
    expect($content)->toContain('urlset');
});
