<?php

declare(strict_types=1);

use App\Models\Content;
use Illuminate\Support\Facades\File;
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
        'slug' => 'test-banana-' . Str::uuid()->toString(),
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
        'slug' => 'test-apple-' . Str::uuid()->toString(),
        'is_published' => true,
    ]);

    $this->artisan('sitemap:generate-food', ['--output' => 'public/test_sitemap.xml'])
        ->assertSuccessful();

    expect(File::exists(base_path('public/test_sitemap.xml')))->toBeTrue();
});
