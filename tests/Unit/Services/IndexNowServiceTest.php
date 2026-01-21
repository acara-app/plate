<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\IndexNowService;
use Exception;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;

beforeEach(function (): void {
    Config::set('services.indexnow.key', 'test-key');
    Config::set('services.indexnow.host', 'www.example.org');
    Config::set('services.indexnow.key_location', 'https://www.example.org/test-key.txt');
});

it('submits URLs successfully', function (): void {
    Http::fake([
        'api.indexnow.org/IndexNow' => Http::response([], 200),
    ]);

    $service = new IndexNowService();
    $result = $service->submit(['https://www.example.org/url1']);

    expect($result->success)->toBeTrue();
    expect($result->urlsSubmitted)->toBe(1);
    expect($result->message)->toContain('Successfully submitted 1 URLs');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.indexnow.org/IndexNow' &&
           $request->data()['host'] === 'www.example.org' &&
           $request->data()['key'] === 'test-key' &&
           $request->data()['urlList'] === ['https://www.example.org/url1'] &&
           $request->data()['keyLocation'] === 'https://www.example.org/test-key.txt');
});

it('handles submission failure', function (): void {
    Http::fake([
        'api.indexnow.org/IndexNow' => Http::response(['error' => 'invalid key'], 400),
    ]);

    Log::shouldReceive('error')->once();

    $service = new IndexNowService();
    $result = $service->submit(['https://www.example.org/url1']);

    expect($result->success)->toBeFalse();
    expect($result->errors)->not->toBeEmpty();
});

it('skips submission if key is missing', function (): void {
    Config::set('services.indexnow.key');

    Log::shouldReceive('warning')->once()->with('IndexNow: No key configured. Skipping submission.');

    $service = new IndexNowService();
    $result = $service->submit(['https://www.example.org/url1']);

    expect($result->success)->toBeFalse();
    expect($result->message)->toContain('key is not configured');
    Http::assertNothingSent();
});

it('returns success for empty URL list', function (): void {
    Log::shouldReceive('info')->once()->with('IndexNow: No URLs to submit.');

    $service = new IndexNowService();
    $result = $service->submit([]);

    expect($result->success)->toBeTrue();
    expect($result->message)->toBe('No URLs to submit.');
    Http::assertNothingSent();
});

it('chunks large URL lists', function (): void {
    Http::fake([
        'api.indexnow.org/IndexNow' => Http::response([], 200),
    ]);

    $urls = array_map(fn ($i): string => "https://www.example.org/url{$i}", range(1, 10005));

    $service = new IndexNowService();
    $result = $service->submit($urls);

    expect($result->success)->toBeTrue();
    expect($result->urlsSubmitted)->toBe(10005);

    Http::assertSentCount(2);

    Http::assertSent(fn (Request $request): bool => count($request->data()['urlList']) === 10000);

    Http::assertSent(fn (Request $request): bool => count($request->data()['urlList']) === 5);
});

it('submits without keyLocation when not configured', function (): void {
    Config::set('services.indexnow.key_location');

    Http::fake([
        'api.indexnow.org/IndexNow' => Http::response([], 200),
    ]);

    $service = new IndexNowService();
    $result = $service->submit(['https://www.example.org/url1']);

    expect($result->success)->toBeTrue();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.indexnow.org/IndexNow' &&
           $request->data()['host'] === 'www.example.org' &&
           $request->data()['key'] === 'test-key' &&
           $request->data()['urlList'] === ['https://www.example.org/url1'] &&
           ! isset($request->data()['keyLocation']));
});

it('handles exceptions during submission', function (): void {
    Http::fake(function (): void {
        throw new Exception('Network error');
    });

    Log::shouldReceive('error')->once()->with(Mockery::pattern('/IndexNow: Exception during submission: Network error/'));

    $service = new IndexNowService();
    $result = $service->submit(['https://www.example.org/url1']);

    expect($result->success)->toBeFalse();
    expect($result->errors)->not->toBeEmpty();
});

it('handles connection timeout during submission', function (): void {
    Http::fake(function (): void {
        throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
    });

    $service = new IndexNowService();
    $result = $service->submit(['https://www.example.org/url1']);

    expect($result->success)->toBeFalse();
    expect($result->errors[0])->toContain('Connection timeout');
});

it('handles partial success when some chunks fail', function (): void {
    $requestCount = 0;

    Http::fake(function () use (&$requestCount) {
        $requestCount++;
        // First request succeeds, second fails
        if ($requestCount === 1) {
            return Http::response([], 200);
        }

        return Http::response(['error' => 'rate limited'], 429);
    });

    // Create URLs that will be chunked (10,005 URLs = 2 chunks)
    $urls = array_map(fn (int $i): string => "https://www.example.org/url{$i}", range(1, 10005));

    $service = new IndexNowService();
    $result = $service->submit($urls);

    expect($result->success)->toBeFalse();
    expect($result->urlsSubmitted)->toBe(10000);
    expect($result->message)->toContain('Partially submitted');
});
