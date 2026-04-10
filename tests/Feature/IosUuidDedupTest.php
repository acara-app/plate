<?php

declare(strict_types=1);

use App\Enums\HealthEntrySource;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Str;

it('deduplicates by sample_uuid when the same UUID is sent twice', function (): void {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();

    HealthSyncSample::factory()->for($user)->heartRate()->create([
        'value' => 72,
        'measured_at' => '2026-04-05 10:00:00',
        'sample_uuid' => $uuid,
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    HealthSyncSample::factory()->for($user)->heartRate()->create([
        'value' => 80,
        'measured_at' => '2026-04-05 11:00:00',
        'sample_uuid' => null,
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $count = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('sample_uuid', $uuid)
        ->count();

    expect($count)->toBe(1);
});

it('stores sample_uuid and ended_at on health sync samples', function (): void {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();

    HealthSyncSample::factory()->for($user)->stepCount()->create([
        'value' => 500,
        'measured_at' => '2026-04-05 10:00:00',
        'ended_at' => '2026-04-05 10:05:00',
        'sample_uuid' => $uuid,
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('sample_uuid', $uuid)
        ->first();

    expect($sample)->not->toBeNull()
        ->and($sample->sample_uuid)->toBe($uuid)
        ->and($sample->ended_at)->not->toBeNull()
        ->and($sample->ended_at->toDateTimeString())->toBe('2026-04-05 10:05:00');
});
