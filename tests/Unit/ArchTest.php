<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->strict();
arch()->preset()->security()->ignoring([
    'assert',
    'sha1',
]);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

//
