<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('clears stored scan results hourly once they are a day old', function (): void {
    $period = DB::table('snap_photo_periods')->insertGetId([
        'subject' => 'guest:retention', 'starts_at' => '2000-01-01 00:00:00', 'ends_at' => null, 'mode' => 'trial', 'allowance' => 2,
    ]);
    $storeScan = fn (string $requestKey, CarbonInterface $createdAt): int => DB::table('snap_photo_requests')->insertGetId([
        'period_id' => $period, 'subject' => 'guest:retention', 'request_key' => $requestKey, 'fingerprint' => 'photo',
        'guest_id' => 'retention', 'status' => 'succeeded', 'attempt' => (string) Str::uuid(), 'source' => 'public_snap_to_track',
        'result' => 'encrypted-analysis', 'created_at' => $createdAt, 'expires_at' => $createdAt->addMinutes(15),
    ]);
    $dayOld = $storeScan('day-old', now('UTC')->subDay()->subMinute());
    $recent = $storeScan('recent', now('UTC')->subHours(23));

    $prune = collect(resolve(Schedule::class)->events())->sole(fn (Event $event): bool => $event->description === 'snap:prune-results');
    $prune->run(app());

    expect($prune->expression)->toBe('0 * * * *')
        ->and(DB::table('snap_photo_requests')->where('id', $dayOld)->first(['status', 'result']))->toEqual((object) ['status' => 'succeeded', 'result' => null])
        ->and(DB::table('snap_photo_requests')->where('id', $recent)->value('result'))->toBe('encrypted-analysis');
});

it('forgets member scans once their result can no longer be replayed', function (): void {
    $storeMemberScan = fn (string $requestKey, CarbonInterface $createdAt): int => DB::table('snap_photo_requests')->insertGetId([
        'period_id' => null, 'subject' => 'user:1', 'request_key' => $requestKey, 'fingerprint' => 'photo',
        'guest_id' => null, 'status' => 'succeeded', 'attempt' => (string) Str::uuid(), 'source' => 'mobile_snap_to_track',
        'result' => 'encrypted-analysis', 'created_at' => $createdAt, 'expires_at' => $createdAt->addMinutes(3),
    ]);
    $storeMemberScan('day-old', now('UTC')->subDay()->subMinute());
    $recent = $storeMemberScan('recent', now('UTC')->subHours(23));

    collect(resolve(Schedule::class)->events())->sole(fn (Event $event): bool => $event->description === 'snap:prune-results')->run(app());

    expect(DB::table('snap_photo_requests')->pluck('id')->all())->toBe([$recent]);
});
