<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security()->ignoring([
    'assert',
    'sha1',
]);

// Apply strict preset but exclude protected method check for models with accessors
arch('strict rules')
    ->preset()->strict()
    ->ignoring([
        App\Models\User::class,
        App\Models\SubscriptionProduct::class, // Has public accessor methods (getXxxAttribute)
    ]);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

//
