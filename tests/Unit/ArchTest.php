<?php

declare(strict_types=1);

use App\Models\MobileSyncDevice;
use App\Models\UserChatPlatformLink;
use Database\Factories\MobileSyncDeviceFactory;
use Database\Factories\UserChatPlatformLinkFactory;

arch()->preset()->php();
arch()->preset()->security()->ignoring([
    'assert',
    'sha1',
    UserChatPlatformLinkFactory::class,
    UserChatPlatformLink::class,
    MobileSyncDeviceFactory::class,
    MobileSyncDevice::class,
]);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();

arch('billing tier resolver does not consult is_verified')
    ->expect('App\Services\Billing')
    ->not->toUse('is_verified');

arch('paywall gating does not consult is_verified')
    ->expect('App\Actions\Billing')
    ->not->toUse('is_verified');

arch('open-core boundary: main does not import the private package')
    ->expect('App')
    ->not->toUse('Acara\AcaraCore');
