<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;

it('translates the AI allowance copy in every language', function (string $locale): void {
    $english = array_keys(Lang::get('common.billing.allowance', [], 'en'));

    expect(array_keys(Lang::get('common.billing.allowance', [], $locale)))->toBe($english);
})->with(['en', 'fr', 'mn']);
