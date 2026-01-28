<?php

declare(strict_types=1);

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

it('flashes a success toast message by default', function (): void {
    toast('Operation completed');

    $flashed = session()->get('inertia.flash_data');

    expect($flashed)->toBeArray()
        ->and($flashed['toast'])->toBe([
            'message' => 'Operation completed',
            'type' => 'success',
        ]);
});

it('flashes a toast message with custom type', function (): void {
    toast('Something went wrong', 'error');

    $flashed = session()->get('inertia.flash_data');

    expect($flashed)->toBeArray()
        ->and($flashed['toast'])->toBe([
            'message' => 'Something went wrong',
            'type' => 'error',
        ]);
});

it('flashes toast messages with various types', function (string $type): void {
    toast('Test message', $type);

    $flashed = session()->get('inertia.flash_data');

    expect($flashed['toast']['type'])->toBe($type);
})->with(['success', 'error', 'warning', 'info']);

it('returns true when premium upgrades are enabled', function (): void {
    config()->set('plate.enable_premium_upgrades', true);
    expect(enable_premium_upgrades())->toBeTrue();
});

it('returns false when premium upgrades are disabled', function (): void {
    config()->set('plate.enable_premium_upgrades', false);
    expect(enable_premium_upgrades())->toBeFalse();
});
