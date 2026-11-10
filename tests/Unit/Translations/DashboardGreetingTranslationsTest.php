<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;

it('greets people by name for every part of the day in every language', function (string $locale): void {
    $greetings = Lang::get('common.dashboard_ai.greeting', [], $locale);

    expect(array_keys($greetings))->toBe(['morning', 'midday', 'evening', 'night'])
        ->and($greetings)->each->toContain('{{name}}')
        ->and(array_keys(Lang::get('common.dashboard_ai.greeting_anonymous', [], $locale)))->toBe(array_keys($greetings));
})->with(['en', 'fr', 'mn']);
