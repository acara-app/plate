<?php

declare(strict_types=1);

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Services\Billing\NullPhotoAnalyses;

it('keeps community photo analysis available without Cloud quota tables', function (): void {
    config()->set('snap.enabled', true);
    $access = resolve(ManagesPhotoAnalyses::class);
    expect($access)->toBeInstanceOf(NullPhotoAnalyses::class)
        ->and($access->enabled())->toBeFalse()
        ->and($access->entitlement(null, null)->limit)->toBeNull();
});
