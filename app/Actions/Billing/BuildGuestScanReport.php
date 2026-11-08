<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Data\Billing\GuestScanReportData;
use App\Models\SnapGuestDailyStat;
use Carbon\CarbonImmutable;

final readonly class BuildGuestScanReport
{
    public function handle(CarbonImmutable $from, CarbonImmutable $to): GuestScanReportData
    {
        $days = SnapGuestDailyStat::query()->whereBetween('date', [$from->toDateString(), $to->toDateString()])->get();
        $totals = ['guests' => 0, 'scans' => 0, 'failed_scans' => 0, 'came_back' => 0, 'signed_in' => 0];
        $guestsByScans = [];

        foreach ($days as $day) {
            $totals['guests'] += $day->guests;
            $totals['scans'] += $day->scans;
            $totals['failed_scans'] += $day->failed_scans;
            $totals['came_back'] += $day->came_back;
            $totals['signed_in'] += $day->signed_in;

            foreach ($day->guests_by_scans as $scans => $guests) {
                $guestsByScans[$scans] = ($guestsByScans[$scans] ?? 0) + $guests;
            }
        }

        ksort($guestsByScans);

        return new GuestScanReportData(
            from: $from->toDateString(),
            to: $to->toDateString(),
            guests: $totals['guests'],
            scans: $totals['scans'],
            failedScans: $totals['failed_scans'],
            guestsByScans: $guestsByScans,
            cameBack: $totals['came_back'],
            signedIn: $totals['signed_in'],
            retentionDays: config()->integer('snap.guest_cookie_days'),
        );
    }
}
