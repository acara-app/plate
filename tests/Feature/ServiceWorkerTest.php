<?php

declare(strict_types=1);

/**
 * @return array<string, array{file: string, isDynamicEntry?: bool, css?: list<string>}>
 */
function viteManifest(): array
{
    /** @var array<string, array{file: string, isDynamicEntry?: bool, css?: list<string>}> */
    return json_decode((string) file_get_contents(public_path('build/manifest.json')), true, flags: JSON_THROW_ON_ERROR);
}

function registeredServiceWorker(): string
{
    $entry = viteManifest()['resources/js/app.tsx']['file'];

    preg_match('~[`"\']/(sw[\w-]*\.js)[`"\']~', (string) file_get_contents(public_path("build/{$entry}")), $matches);

    return $matches[1] ?? '';
}

function serviceWorker(): string
{
    return (string) file_get_contents(public_path(registeredServiceWorker()));
}

/**
 * @return list<string>
 */
function precachedUrls(): array
{
    preg_match_all('/\{url:"([^"]+)"/', serviceWorker(), $matches);

    return $matches[1];
}

it('precaches the app shell and the web manifest', function (): void {
    $entry = viteManifest()['resources/js/app.tsx'];

    expect(precachedUrls())->toContain(
        'build/manifest.webmanifest',
        'build/'.$entry['file'],
        ...array_map(fn (string $stylesheet): string => 'build/'.$stylesheet, $entry['css'] ?? []),
    );
});

it('leaves lazily loaded chunks out of the precache', function (): void {
    $lazyChunks = collect(viteManifest())
        ->whereStrict('isDynamicEntry', true)
        ->map(fn (array $chunk): string => 'build/'.$chunk['file'])
        ->values()
        ->all();

    expect($lazyChunks)->not->toBeEmpty()
        ->and(precachedUrls())->each->toStartWith('build/')->not->toBeIn($lazyChunks);
});

it('precaches only files that exist in the build', function (): void {
    foreach (precachedUrls() as $url) {
        expect(public_path($url))->toBeFile();
    }
});

it('caches build assets at runtime on first use', function (): void {
    expect(serviceWorker())->toContain('CacheFirst', '"build-assets"', '"/build/assets/"');
});
