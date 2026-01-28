<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Config::set('services.indexnow.key', 'test-key-12345');
    Config::set('services.indexnow.host', 'plate.acara.app');
    Config::set('services.indexnow.key_location', 'https://plate.acara.app/test-key.txt');

    if (! File::isDirectory(public_path('test_temp'))) {
        File::makeDirectory(public_path('test_temp'));
    }
});

afterEach(function (): void {
    if (File::isDirectory(public_path('test_temp'))) {
        File::deleteDirectory(public_path('test_temp'));
    }
});

it('extracts and submits URLs from sitemap fixtures', function (): void {
    Http::fake([
        'api.indexnow.org/IndexNow' => Http::response([], 200),
    ]);

    File::copy(
        base_path('tests/Fixtures/Sitemaps/simple_sitemap.xml'),
        public_path('test_temp/sitemap1.xml')
    );
    File::copy(
        base_path('tests/Fixtures/Sitemaps/no_ns_sitemap.xml'),
        public_path('test_temp/sitemap2.xml')
    );

    $this->artisan('sitemap:indexnow', [
        '--file' => ['test_temp/sitemap1.xml', 'test_temp/sitemap2.xml'],
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Found 2 URLs in test_temp/sitemap1.xml')
        ->expectsOutputToContain('Found 1 URLs in test_temp/sitemap2.xml')
        ->expectsOutputToContain('Submitting 3 unique URLs to IndexNow')
        ->expectsOutputToContain('Successfully submitted 3 URLs to IndexNow');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.indexnow.org/IndexNow' &&
           count($request->data()['urlList']) === 3 &&
           in_array('https://plate.acara.app/page1', $request->data()['urlList']) &&
           in_array('https://plate.acara.app/page2', $request->data()['urlList']) &&
           in_array('https://plate.acara.app/no-ns-page1', $request->data()['urlList']));
});

it('handles missing sitemap files', function (): void {
    Http::fake();

    $this->artisan('sitemap:indexnow', [
        '--file' => ['non_existent_sitemap.xml'],
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Sitemap file not found: non_existent_sitemap.xml')
        ->expectsOutputToContain('No URLs found to submit');

    Http::assertNothingSent();
});

it('handles submission errors', function (): void {
    Http::fake([
        'api.indexnow.org/IndexNow' => Http::response(['error' => 'invalid'], 400),
    ]);

    File::copy(
        base_path('tests/Fixtures/Sitemaps/simple_sitemap.xml'),
        public_path('test_temp/sitemap.xml')
    );

    $this->artisan('sitemap:indexnow', [
        '--file' => ['test_temp/sitemap.xml'],
    ])
        ->assertFailed()
        ->expectsOutputToContain('Failed to submit URLs to IndexNow');
});

it('uses default files when no file option is provided', function (): void {
    Http::fake([
        'api.indexnow.org/IndexNow' => Http::response([], 200),
    ]);

    File::copy(
        base_path('tests/Fixtures/Sitemaps/simple_sitemap.xml'),
        public_path('test_temp/sitemap.xml')
    );
    File::copy(
        base_path('tests/Fixtures/Sitemaps/no_ns_sitemap.xml'),
        public_path('test_temp/food_sitemap.xml')
    );

    $this->artisan('sitemap:indexnow', [
        '--file' => ['test_temp/sitemap.xml', 'test_temp/food_sitemap.xml'],
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Processing test_temp/sitemap.xml')
        ->expectsOutputToContain('Processing test_temp/food_sitemap.xml');

    Http::assertSent(fn (Request $request): bool => count($request->data()['urlList']) === 3);
});

it('handles invalid XML files gracefully', function (): void {
    Http::fake();

    File::put(public_path('test_temp/invalid.xml'), '<?xml version="1.0"?><root><unclosed>');

    $this->artisan('sitemap:indexnow', [
        '--file' => ['test_temp/invalid.xml'],
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('No URLs found to submit');

    Http::assertNothingSent();
});

it('handles exceptions during XML parsing', function (): void {
    Http::fake();

    File::put(public_path('test_temp/broken.xml'), '<?xml version="1.0"?><broken><unclosed>');

    $this->artisan('sitemap:indexnow', [
        '--file' => ['test_temp/broken.xml'],
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('No URLs found to submit');

    Http::assertNothingSent();
});

it('handles empty XML files', function (): void {
    Http::fake();

    File::put(public_path('test_temp/empty.xml'), '');

    $this->artisan('sitemap:indexnow', [
        '--file' => ['test_temp/empty.xml'],
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('No URLs found to submit');

    Http::assertNothingSent();
});

it('shows detailed error when key is not configured', function (): void {
    Config::set('services.indexnow.key');

    File::copy(
        base_path('tests/Fixtures/Sitemaps/simple_sitemap.xml'),
        public_path('test_temp/sitemap.xml')
    );

    $this->artisan('sitemap:indexnow', [
        '--file' => ['test_temp/sitemap.xml'],
    ])
        ->assertFailed()
        ->expectsOutputToContain('key is not configured');

    Http::assertNothingSent();
});
