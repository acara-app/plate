<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;

it('offers every starter as a label and a question in every language', function (string $locale): void {
    $starters = Lang::get('common.chat.starters', [], $locale);

    expect(array_keys($starters))->toBe(['label', 'breakfast', 'lunch', 'dinner', 'tomorrow', 'glucose', 'water', 'workout', 'sleep'])
        ->and(array_filter($starters, is_array(...)))->each->toHaveKeys(['label', 'prompt']);
})->with(['en', 'fr', 'mn']);
