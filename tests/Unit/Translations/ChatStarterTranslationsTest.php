<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;

it('labels every starter and translates the chat questions in every language', function (string $locale): void {
    $starters = Lang::get('common.chat.starters', [], $locale);

    expect(array_keys($starters))->toBe(['label', 'breakfast', 'lunch', 'dinner', 'tomorrow', 'water', 'workout', 'sleep'])
        ->and($starters['water'])->toHaveKey('label')
        ->and(Arr::except($starters, ['label', 'water']))->each->toHaveKeys(['label', 'prompt']);
})->with(['en', 'fr', 'mn']);
