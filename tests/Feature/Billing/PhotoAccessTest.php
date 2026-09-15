<?php

declare(strict_types=1);

use Acara\AcaraCore\Services\Billing\CloudPhotoAnalyses;
use App\Contracts\Billing\ManagesPhotoAnalyses;

it('binds the Cloud extension and enforces its daily photo allowance', function (): void {
    config()->set('snap.enabled', true);
    $access = resolve(ManagesPhotoAnalyses::class);
    expect($access)->toBeInstanceOf(CloudPhotoAnalyses::class)
        ->and($access->enabled())->toBeTrue()
        ->and($access->entitlement(null, 'browser')->limit)->toBe(1);
});
