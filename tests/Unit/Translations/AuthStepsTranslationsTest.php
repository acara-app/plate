<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;

it('has the log in and sign up copy in every language', function (string $locale, string $section): void {
    $english = Lang::get("auth.{$section}", [], 'en');
    $translated = Lang::get("auth.{$section}", [], $locale);

    expect(array_keys($translated))->toBe(array_keys($english))
        ->and($translated)->each->not->toBeEmpty();
})->with(['fr', 'mn'])->with(['login', 'register']);
