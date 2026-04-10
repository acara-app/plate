<?php

declare(strict_types=1);

use App\Utilities\LanguageUtil;

covers(LanguageUtil::class);

it('returns all languages', function (): void {
    expect(LanguageUtil::all())
        ->toBe(['en' => 'English', 'fr' => 'Français', 'mn' => 'Монгол']);
});

it('returns language keys', function (): void {
    expect(LanguageUtil::keys())->toBe(['en', 'fr', 'mn']);
});

it('returns default language', function (): void {
    expect(LanguageUtil::default())->toBe('en');
});

dataset('valid languages', [
    'English' => ['en', 'English'],
    'Français' => ['fr', 'Français'],
    'Монгол' => ['mn', 'Монгол'],
]);

it('gets language by code', function (string $code, string $label): void {
    expect(LanguageUtil::get($code))->toBe($label);
})->with('valid languages');

it('returns null for unknown code', function (): void {
    expect(LanguageUtil::get('xx'))->toBeNull();
});

it('checks language exists', function (string $code): void {
    expect(LanguageUtil::has($code))->toBeTrue();
})->with('valid languages');

it('returns false for unknown code', function (): void {
    expect(LanguageUtil::has('xx'))->toBeFalse();
});
