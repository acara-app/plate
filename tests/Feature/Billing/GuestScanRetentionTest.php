<?php

declare(strict_types=1);

use App\Models\SnapPhotoAccount;
use App\Models\SnapPhotoPeriod;
use App\Models\SnapPhotoRequest;
use Carbon\CarbonImmutable;

covers(SnapPhotoAccount::class);

it('deletes guest scan records once the guest cookie has expired, keeping recent and signed-in guests', function (): void {
    $guest = function (string $subject, ?string $owner, CarbonImmutable ...$scannedAt): void {
        SnapPhotoAccount::factory()->create(['subject' => $subject, 'owner' => $owner]);
        $period = SnapPhotoPeriod::factory()->create(['subject' => $subject]);

        foreach ($scannedAt as $at) {
            SnapPhotoRequest::factory()->create(['period_id' => $period->id, 'subject' => $subject, 'status' => 'succeeded', 'created_at' => $at, 'expires_at' => $at->addMinutes(15)]);
        }
    };

    $guest('guest:expired', null, now('UTC')->subDays(366), now('UTC')->subDays(200));
    $guest('guest:recent', null, now('UTC')->subDays(364));
    $guest('guest:signed-in', 'user:7', now('UTC')->subDays(400));
    $guest('guest:never-scanned', null);

    $this->artisan('model:prune', ['--model' => [SnapPhotoAccount::class]])->assertSuccessful();

    expect(SnapPhotoAccount::query()->orderBy('subject')->pluck('subject')->all())->toBe(['guest:recent', 'guest:signed-in'])
        ->and(SnapPhotoPeriod::query()->orderBy('subject')->pluck('subject')->all())->toBe(['guest:recent', 'guest:signed-in'])
        ->and(SnapPhotoRequest::query()->orderBy('subject')->pluck('subject')->all())->toBe(['guest:recent', 'guest:signed-in']);
});
