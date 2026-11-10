<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;

it('translates the meal scanner copy in every language', function (string $group, string $locale): void {
    $english = array_keys(Arr::dot(Lang::get('common.snap_to_track.'.$group, [], 'en')));

    expect(array_keys(Arr::dot(Lang::get('common.snap_to_track.'.$group, [], $locale))))->toBe($english);
})->with(['camera', 'index', 'analysis'])->with(['en', 'fr', 'mn']);
