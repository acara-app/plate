<?php

declare(strict_types=1);

use App\Actions\Billing\AggregateDailyGuestScans;
use App\Actions\Billing\BuildGuestScanReport;
use App\Console\Commands\AggregateDailyGuestScansCommand;
use App\Console\Commands\ReportGuestScansCommand;
use App\Data\Billing\GuestScanReportData;
use App\Models\SnapGuestDailyStat;
use App\Models\SnapPhotoAccount;
use App\Models\SnapPhotoPeriod;
use App\Models\SnapPhotoRequest;
use Carbon\CarbonImmutable;

covers(AggregateDailyGuestScans::class, BuildGuestScanReport::class, AggregateDailyGuestScansCommand::class, ReportGuestScansCommand::class, GuestScanReportData::class, SnapGuestDailyStat::class);

it('reports how guests use their free scans, including days whose guest records are already deleted', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-08 12:00:00', 'UTC'));

    $this->artisan('snap:guest-scans-report')->expectsOutputToContain('No guest scans in this period.')->assertSuccessful();

    $guest = function (string $subject, array $scans, ?string $owner = null): void {
        SnapPhotoAccount::factory()->create(['subject' => $subject, 'owner' => $owner]);
        $period = SnapPhotoPeriod::factory()->create(['subject' => $subject]);

        foreach ($scans as $at => $status) {
            SnapPhotoRequest::factory()->create(['period_id' => $period->id, 'subject' => $subject, 'status' => $status, 'created_at' => $at, 'expires_at' => CarbonImmutable::parse($at)->addMinutes(15)]);
        }
    };

    $guest('guest:one-scan', ['2026-11-02 09:00:00' => 'succeeded']);
    $guest('guest:came-back', ['2026-11-02 10:00:00' => 'succeeded', '2026-11-05 10:00:00' => 'succeeded']);
    $guest('guest:failed', ['2026-11-02 11:00:00' => 'failed']);
    $guest('guest:signed-in', ['2026-11-03 08:00:00' => 'succeeded'], 'user:7');
    $guest('guest:today', ['2026-11-08 09:00:00' => 'succeeded']);
    SnapGuestDailyStat::factory()->create(['date' => '2025-10-20', 'guests' => 6, 'scans' => 6, 'failed_scans' => 0, 'guests_by_scans' => [1 => 6], 'came_back' => 0, 'signed_in' => 1]);

    $this->artisan('snap:aggregate-guest-scans')->assertSuccessful();

    $this->artisan('snap:guest-scans-report', ['--days' => 400])
        ->expectsOutputToContain('10 guests · 10 successful scans · 1 failed (9%)')
        ->expectsTable(['Guests', 'Count', 'Share'], [
            ['No successful scan', 1, '10%'],
            ['1 successful scan', 8, '80%'],
            ['2 successful scans', 1, '10%'],
            ['Came back on a later day', 1, '10%'],
            ['Signed up or logged in afterwards', 2, '20%'],
        ])
        ->assertSuccessful();
});
