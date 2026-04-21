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
