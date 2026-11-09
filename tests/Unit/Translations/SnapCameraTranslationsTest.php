<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;

it('translates the meal camera copy in every language', function (string $locale): void {
    $english = array_keys(Lang::get('common.snap_to_track.camera', [], 'en'));

    expect(array_keys(Lang::get('common.snap_to_track.camera', [], $locale)))->toBe($english);
})->with(['en', 'fr', 'mn']);
