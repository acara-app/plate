<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;

it('has every onboarding step fully translated', function (string $locale, string $step): void {
    $english = Arr::dot(Lang::get("common.onboarding.{$step}", [], 'en'));
    $translated = Arr::dot(Lang::get("common.onboarding.{$step}", [], $locale));

    expect(array_keys($translated))->toBe(array_keys($english))
        ->and($translated)->each->not->toBeEmpty();
})->with(['fr', 'mn'])->with(['biometrics', 'identity', 'dietary_preferences']);
