<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

it('returns true when app is in production', function (): void {
    app()->detectEnvironment(fn () => 'production');

    expect(isProduction())->toBeTrue();
});

it('returns false when app is not in production', function (): void {
    app()->detectEnvironment(fn () => 'local');

    expect(isProduction())->toBeFalse();
});

it('returns true when app is in local', function (): void {
    app()->detectEnvironment(fn () => 'local');

    expect(isLocal())->toBeTrue();
});

it('returns false when app is not in local', function (): void {
    app()->detectEnvironment(fn () => 'production');

    expect(isLocal())->toBeFalse();
});

it('paginates query from request with default per page', function (): void {
    $query = App\Models\User::query();

    request()->merge(['page' => 1, 'perPage' => 25]);

    $result = paginateFromRequest($query);

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});

it('paginates query from request with custom per page', function (): void {
    $query = App\Models\User::query();

    request()->merge(['page' => 1, 'perPage' => 10]);

    $result = paginateFromRequest($query, 10);

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});

it('appends unique id to filename', function (): void {
    $file = UploadedFile::fake()->image('photo1.png');

    $result = appendUniqueIdToFilename($file);

    expect($result)
        ->toBeString()
        ->toContain('photo1_')
        ->toEndWith('.png');
});

it('appends unique id to filename without extension', function (): void {
    $file = UploadedFile::fake()->create('document');

    $result = appendUniqueIdToFilename($file);

    expect($result)
        ->toBeString()
        ->toContain('document_');
});

it('makes filename unique from url', function (): void {
    $url = 'https://example.com/images/photo1.png';

    $result = makeFilenameUniqueFromUrl($url);

    expect($result)
        ->toBeString()
        ->toContain('photo1_')
        ->toEndWith('.png');
});

it('makes filename unique from url without extension', function (): void {
    $url = 'https://example.com/images/photo1';

    $result = makeFilenameUniqueFromUrl($url);

    expect($result)
        ->toBeString()
        ->toContain('photo1_')
        ->toEndWith('.jpg');
});

it('extracts extension from url', function (): void {
    expect(extensionFromUrl('https://example.com/photo.png'))->toBe('png')
        ->and(extensionFromUrl('https://example.com/photo.jpg'))->toBe('jpg')
        ->and(extensionFromUrl('https://example.com/photo'))->toBe('jpg');
});

it('removes hyphen and capitalizes string', function (): void {
    expect(removeHyphenAndCapitalize('hello-world'))->toBe('Hello World')
        ->and(removeHyphenAndCapitalize('test-case-example'))->toBe('Test Case Example');
});

it('returns mime type for known extensions', function (): void {
    expect(getMimeType('file.txt'))->toBe('text/plain')
        ->and(getMimeType('file.html'))->toBe('text/html')
        ->and(getMimeType('file.css'))->toBe('text/css')
        ->and(getMimeType('file.js'))->toBe('application/javascript')
        ->and(getMimeType('file.json'))->toBe('application/json')
        ->and(getMimeType('file.xml'))->toBe('application/xml')
        ->and(getMimeType('file.png'))->toBe('image/png')
        ->and(getMimeType('file.jpg'))->toBe('image/jpeg')
        ->and(getMimeType('file.jpeg'))->toBe('image/jpeg')
        ->and(getMimeType('file.gif'))->toBe('image/gif')
        ->and(getMimeType('file.webp'))->toBe('image/webp')
        ->and(getMimeType('file.avif'))->toBe('image/avif')
        ->and(getMimeType('file.svg'))->toBe('image/svg+xml')
        ->and(getMimeType('file.pdf'))->toBe('application/pdf')
        ->and(getMimeType('file.zip'))->toBe('application/zip')
        ->and(getMimeType('file.mp3'))->toBe('audio/mpeg')
        ->and(getMimeType('file.map'))->toBe('application/javascript');
});

it('returns default mime type for unknown extensions', function (): void {
    expect(getMimeType('file.unknown'))->toBe('application/octet-stream');
});

it('makes key from string', function (): void {
    expect(makeKey('simple-key'))->toBe('simple-key');
});

it('makes key from array', function (): void {
    expect(makeKey(['key1', 'key2', 'key3']))->toBe('key1|key2|key3');
});

it('makes key from long string as md5', function (): void {
    $longKey = str_repeat('a', 201);

    $result = makeKey($longKey);

    expect($result)->toBe(md5($longKey));
});

it('flattens value array', function (): void {
    $input = [
        ['value' => 'item1'],
        ['value' => 'item2'],
        ['value' => 'item3'],
    ];

    expect(flattenValue($input))->toBe(['item1', 'item2', 'item3']);
});

it('reverts flattened value array', function (): void {
    $input = ['item1', 'item2', 'item3'];

    expect(revertFlattenedValue($input))->toBe([
        ['value' => 'item1'],
        ['value' => 'item2'],
        ['value' => 'item3'],
    ]);
});

it('checks if value is not null', function (): void {
    expect(isNotNull('value'))->toBeTrue()
        ->and(isNotNull(123))->toBeTrue()
        ->and(isNotNull(null))->toBeFalse()
        ->and(isNotNull(''))->toBeFalse()
        ->and(isNotNull('0'))->toBeFalse();
});

it('converts enum to value label array', function (): void {
    $options = [
        'key1' => 'Label 1',
        'key2' => 'Label 2',
        'key3' => 'Label 3',
    ];

    $result = enumToValueLabelArray($options);

    expect($result)->toBe([
        ['label' => 'Label 1', 'value' => 'key1'],
        ['label' => 'Label 2', 'value' => 'key2'],
        ['label' => 'Label 3', 'value' => 'key3'],
    ]);
});

it('clears all filters when no fields specified', function (): void {
    $request = Request::create('/test?filter1=value1&filter2=value2', 'GET');

    $result = clearFilters($request);

    expect($result)->toBe('http://localhost?');
});

it('clears specific filters', function (): void {
    $request = Request::create('/test?filter1=value1&filter2=value2&page=1', 'GET');

    $result = clearFilters($request, ['filter1', 'filter2']);

    expect($result)->toBe('http://localhost?page=1');
});

it('gets filtered params from request', function (): void {
    $request = Request::create('/test?filter1=value1&filter2=value2&page=1', 'GET');

    $result = filteredParams($request, ['filter1', 'filter2']);

    expect($result)->toBe([
        'filter1' => 'value1',
        'filter2' => 'value2',
    ]);
});
