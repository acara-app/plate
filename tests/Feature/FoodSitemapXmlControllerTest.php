<?php

declare(strict_types=1);

use App\Models\Content;
use Illuminate\Support\Facades\Storage;

it('returns food sitemap as xml', function (): void {
    Content::factory()->create([
        'slug' => 'test-food',
        'title' => 'Test Food',
    ]);

    $response = $this->get(route('food.sitemap'));

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/xml');

    $content = $response->getContent();
    expect($content)->toContain('test-food')
        ->toContain('<?xml version="1.0"')
        ->toContain('urlset');
});

it('only includes published foods', function (): void {
    Content::factory()->create(['slug' => 'published-food']);
    Content::factory()->unpublished()->create(['slug' => 'unpublished-food']);

    $response = $this->get(route('food.sitemap'));

    $response->assertStatus(200);

    $content = $response->getContent();
    expect($content)->toContain('published-food')
        ->not->toContain('unpublished-food');
});

it('includes food image when available', function (): void {
    Storage::fake('s3_public');

    Content::factory()
        ->withImage()
        ->create(['slug' => 'food-with-image']);

    $response = $this->get(route('food.sitemap'));

    $response->assertStatus(200);

    $content = $response->getContent();
    expect($content)->toContain('image:image')
        ->toContain('food-images/');
});
