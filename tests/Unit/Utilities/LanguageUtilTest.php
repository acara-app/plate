<?php

declare(strict_types=1);

use App\Utilities\LanguageUtil;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

covers(LanguageUtil::class);

it('returns all languages', function (): void {
    expect(LanguageUtil::all())
        ->toBe(['en' => 'English', 'mn' => 'Монгол']);
});

it('returns language keys', function (): void {
    expect(LanguageUtil::keys())->toBe(['en', 'mn']);
});

it('returns default language', function (): void {
    expect(LanguageUtil::default())->toBe('en');
});

dataset('valid languages', [
    'English' => ['en', 'English'],
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

it('returns empty array when locale directory does not exist', function (): void {
    File::shouldReceive('isDirectory')->with(lang_path('xx'))->andReturn(false);

    expect(LanguageUtil::translations('xx'))->toBe([]);
});

it('returns translations for existing locale', function (): void {
    $filePath = lang_path('en/auth.php');

    File::shouldReceive('isDirectory')->with(lang_path('en'))->andReturn(true);
    File::shouldReceive('files')->with(lang_path('en'))->andReturn([
        new SplFileInfo($filePath, lang_path('en'), 'auth.php'),
    ]);

    $result = LanguageUtil::translations('en');

    expect($result)->toHaveKey('auth');
});
