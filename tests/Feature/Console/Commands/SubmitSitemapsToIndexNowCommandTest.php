<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\SubmitSitemapsToIndexNowCommand;
use App\Models\Content;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

covers(SubmitSitemapsToIndexNowCommand::class);

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

it('handles empty string and non-string file options', function (): void {
    Http::fake();

    $this->artisan('sitemap:indexnow', [
        '--file' => ['', null],
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('No URLs found to submit');

    Http::assertNothingSent();
});

it('uses default sitemap.xml when no file option is provided and file does not exist', function (): void {
    Http::fake();

    if (File::exists(public_path('sitemap.xml'))) {
        File::move(public_path('sitemap.xml'), public_path('sitemap.xml.bak'));
    }

    $this->artisan('sitemap:indexnow')
        ->assertSuccessful()
        ->expectsOutputToContain('Sitemap file not found: sitemap.xml')
        ->expectsOutputToContain('No URLs found to submit');

    if (File::exists(public_path('sitemap.xml.bak'))) {
        File::move(public_path('sitemap.xml.bak'), public_path('sitemap.xml'));
    }
});

it('handles simplexml_load_file returning false (with internal errors)', function (): void {
    Http::fake();
    $internal = libxml_use_internal_errors(true);
    try {
        File::put(public_path('test_temp/failing.xml'), 'not xml');
        $this->artisan('sitemap:indexnow', [
            '--file' => ['test_temp/failing.xml'],
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('No URLs found to submit');
    } finally {
        libxml_use_internal_errors($internal);
    }
});

it('handles simplexml_load_file throwing an exception', function (): void {
    Http::fake();
    File::put(public_path('test_temp/broken.xml'), '<?xml version="1.0"?><broken><unclosed>');

    $this->artisan('sitemap:indexnow', [
        '--file' => ['test_temp/broken.xml'],
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('No URLs found to submit');
});

it('includes food URLs from database alongside sitemap URLs', function (): void {
    Http::fake([
        'api.indexnow.org/IndexNow' => Http::response([], 200),
    ]);

    Content::factory()->create(['slug' => 'banana', 'is_published' => true]);
    Content::factory()->create(['slug' => 'apple', 'is_published' => true]);

    File::copy(
        base_path('tests/Fixtures/Sitemaps/simple_sitemap.xml'),
        public_path('test_temp/sitemap.xml')
    );

    $this->artisan('sitemap:indexnow', [
        '--file' => ['test_temp/sitemap.xml'],
    ])
        ->assertSuccessful();

    Http::assertSent(function (Request $request): bool {
        $urlList = $request->data()['urlList'];

        return count($urlList) === 4
            && in_array('https://plate.acara.app/page1', $urlList)
            && in_array('https://plate.acara.app/page2', $urlList)
            && in_array(route('food.show', 'banana'), $urlList)
            && in_array(route('food.show', 'apple'), $urlList);
    });
});

it('excludes unpublished foods from submission', function (): void {
    Http::fake([
        'api.indexnow.org/IndexNow' => Http::response([], 200),
    ]);

    Content::factory()->create(['slug' => 'published-food', 'is_published' => true]);
    Content::factory()->unpublished()->create(['slug' => 'unpublished-food']);

    File::copy(
        base_path('tests/Fixtures/Sitemaps/simple_sitemap.xml'),
        public_path('test_temp/sitemap.xml')
    );

    $this->artisan('sitemap:indexnow', [
        '--file' => ['test_temp/sitemap.xml'],
    ])
        ->assertSuccessful();

    Http::assertSent(function (Request $request): bool {
        $urlList = $request->data()['urlList'];

        return in_array(route('food.show', 'published-food'), $urlList)
            && ! in_array(route('food.show', 'unpublished-food'), $urlList);
    });
});

it('includes post URLs for both english and non-english locales', function (): void {
    Http::fake([
        'api.indexnow.org/IndexNow' => Http::response([], 200),
    ]);

    Content::factory()->post()->create(['slug' => 'my-english-post', 'locale' => 'en', 'is_published' => true]);
    Content::factory()->post()->create(['slug' => 'my-mongolian-post', 'locale' => 'mn', 'is_published' => true]);

    $this->artisan('sitemap:indexnow', [
        '--file' => ['non_existent.xml'],
    ])
        ->assertSuccessful();

    Http::assertSent(function (Request $request): bool {
        $urlList = $request->data()['urlList'];

        return in_array(route('post.show', 'my-english-post'), $urlList)
            && in_array(route('post.locale.show', ['locale' => 'mn', 'slug' => 'my-mongolian-post']), $urlList);
    });
});

it('submits only food URLs when no sitemap files exist', function (): void {
    Http::fake([
        'api.indexnow.org/IndexNow' => Http::response([], 200),
    ]);

    Content::factory()->create(['slug' => 'banana', 'is_published' => true]);

    $this->artisan('sitemap:indexnow', [
        '--file' => ['non_existent.xml'],
    ])
        ->assertSuccessful();

    Http::assertSent(function (Request $request): bool {
        $urlList = $request->data()['urlList'];

        return count($urlList) === 1
            && in_array(route('food.show', 'banana'), $urlList);
    });
});
