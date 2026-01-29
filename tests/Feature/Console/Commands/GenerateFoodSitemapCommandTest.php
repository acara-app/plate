<?php

declare(strict_types=1);

use App\Models\Content;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    // Clean up any existing sitemap
    if (File::exists(base_path('public/food_sitemap.xml'))) {
        File::delete(base_path('public/food_sitemap.xml'));
    }
});

afterEach(function (): void {
    // Clean up
    if (File::exists(base_path('public/food_sitemap.xml'))) {
        File::delete(base_path('public/food_sitemap.xml'));
    }
    if (File::exists(base_path('public/test_sitemap.xml'))) {
        File::delete(base_path('public/test_sitemap.xml'));
    }
});

it('generates a sitemap for published food pages', function (): void {
    Content::factory()->create([
        'slug' => 'test-banana-'.Str::uuid()->toString(),
        'is_published' => true,
    ]);

    $this->artisan('sitemap:generate-food')
        ->assertSuccessful()
        ->expectsOutputToContain('Generated sitemap');

    expect(File::exists(base_path('public/food_sitemap.xml')))->toBeTrue();
});

it('warns when no published food pages found', function (): void {
    $this->artisan('sitemap:generate-food')
        ->assertSuccessful()
        ->expectsOutputToContain('No published food pages found');
});

it('generates sitemap to custom output path', function (): void {
    Content::factory()->create([
        'slug' => 'test-apple-'.Str::uuid()->toString(),
        'is_published' => true,
    ]);

    $this->artisan('sitemap:generate-food', ['--output' => 'public/test_sitemap.xml'])
        ->assertSuccessful();

    expect(File::exists(base_path('public/test_sitemap.xml')))->toBeTrue();
});

it('includes image tags in sitemap when food has image', function (): void {
    Content::factory()->create([
        'slug' => 'apple',
        'title' => 'Apple',
        'image_path' => 'food-images/apple.png', // Assuming factory/model handles full URL generation
        'is_published' => true,
        'type' => \App\Enums\ContentType::Food,
    ]);

    // Mock storage if needed, or rely on existing model behavior.
    // The Content model uses Storage::disk('s3_public')->url($this->image_path).
    // We might need to mock Storage facade to return a predictable URL.
    Storage::fake('s3_public');
    
    $this->artisan('sitemap:generate-food')
        ->assertSuccessful();
    
    $content = File::get(base_path('public/food_sitemap.xml'));
    
    expect($content)
        ->toContain('<image:image>')
        ->toContain('<image:loc>http://plate.test/storage/food-images/apple.png</image:loc>') // Default fake storage URL structure? We'll check.
        ->toContain('<image:title>Apple Glycemic Index</image:title>');
});
