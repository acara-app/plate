<?php

declare(strict_types=1);

use App\Enums\ContentType;
use App\Models\Content;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    if (File::exists(base_path('public/food_sitemap.xml'))) {
        File::delete(base_path('public/food_sitemap.xml'));
    }
});

afterEach(function (): void {
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
        'image_path' => 'food-images/apple.png',
        'is_published' => true,
        'type' => ContentType::Food,
    ]);

    Storage::fake('s3_public');

    $imagePath = 'food-images/apple.png';
    $storageUrl = Storage::disk('s3_public')->url($imagePath);

    $expectedUrl = Str::startsWith($storageUrl, ['http://', 'https://'])
        ? $storageUrl
        : url($storageUrl);

    $this->artisan('sitemap:generate-food')
        ->assertSuccessful();

    $content = File::get(base_path('public/food_sitemap.xml'));

    expect($content)
        ->toContain('<image:image>')
        ->toContain(sprintf('<image:loc>%s</image:loc>', $expectedUrl))
        ->toContain('<image:title>Apple Glycemic Index</image:title>');
});
