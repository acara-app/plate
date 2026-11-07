<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function runIncreaseSnapLifetimeAllowanceMigration(): void
{
    (require database_path('migrations/2026_10_24_033047_increase_snap_lifetime_allowance.php'))->up();
}

it('raises one-scan lifetime trials to two scans without touching ownership or in-flight scans', function (): void {
    DB::table('snap_photo_accounts')->insert(['subject' => 'guest:existing', 'owner' => 'user:7']);
    $lifetimeTrial = DB::table('snap_photo_periods')->insertGetId([
        'subject' => 'user:7', 'starts_at' => '2000-01-01 00:00:00', 'ends_at' => null, 'mode' => 'trial', 'allowance' => 1,
    ]);
    $endedTrial = DB::table('snap_photo_periods')->insertGetId([
        'subject' => 'user:8', 'starts_at' => '2000-01-01 00:00:00', 'ends_at' => '2026-10-01 00:00:00', 'mode' => 'trial', 'allowance' => 1,
    ]);
    foreach (['succeeded', 'pending'] as $status) {
        DB::table('snap_photo_requests')->insert([
            'period_id' => $lifetimeTrial, 'subject' => 'guest:existing', 'request_key' => $status,
            'fingerprint' => 'fixture', 'guest_id' => 'existing', 'status' => $status,
            'attempt' => (string) Str::uuid(), 'source' => 'public_snap_to_track',
            'created_at' => now(), 'expires_at' => now()->addMinutes(15),
        ]);
    }

    $scansBefore = DB::table('snap_photo_requests')->get()->toJson();

    runIncreaseSnapLifetimeAllowanceMigration();
    runIncreaseSnapLifetimeAllowanceMigration();

    expect(DB::table('snap_photo_periods')->where('id', $lifetimeTrial)->value('allowance'))->toBe(2)
        ->and(DB::table('snap_photo_periods')->where('id', $endedTrial)->value('allowance'))->toBe(1)
        ->and(DB::table('snap_photo_requests')->get()->toJson())->toBe($scansBefore)
        ->and(DB::table('snap_photo_accounts')->where('subject', 'guest:existing')->value('owner'))->toBe('user:7');
});
