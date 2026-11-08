<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Models\SnapGuestDailyStat;
use App\Models\SnapPhotoAccount;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class AggregateDailyGuestScans
{
    public function handle(): void
    {
        $from = SnapPhotoAccount::guestCookieExpiredBefore();
        $stats = [];

        $guests = DB::table('snap_photo_requests')
            ->leftJoin('snap_photo_accounts', 'snap_photo_accounts.subject', '=', 'snap_photo_requests.subject')
            ->where('snap_photo_requests.subject', 'like', 'guest:%')
            ->groupBy('snap_photo_requests.subject', 'snap_photo_accounts.owner')
            ->havingRaw('min(snap_photo_requests.created_at) >= ?', [$from])
            ->havingRaw('min(snap_photo_requests.created_at) < ?', [CarbonImmutable::today('UTC')])
            ->select('snap_photo_accounts.owner')
            ->selectRaw('min(snap_photo_requests.created_at) as first_scan_at')
            ->selectRaw('max(snap_photo_requests.created_at) as last_scan_at')
            ->selectRaw("sum(case when snap_photo_requests.status = 'succeeded' then 1 else 0 end) as scans")
            ->selectRaw("sum(case when snap_photo_requests.status = 'failed' then 1 else 0 end) as failed_scans")
            ->cursor();

        foreach ($guests as $guest) {
            [$date, $lastScanDate, $scans, $failedScans, $signedIn] = $this->guest($guest);

            $stats[$date] ??= ['date' => $date, 'guests' => 0, 'scans' => 0, 'failed_scans' => 0, 'guests_by_scans' => [], 'came_back' => 0, 'signed_in' => 0];
            $stats[$date]['guests']++;
            $stats[$date]['scans'] += $scans;
            $stats[$date]['failed_scans'] += $failedScans;
            $stats[$date]['guests_by_scans'][$scans] = ($stats[$date]['guests_by_scans'][$scans] ?? 0) + 1;
            $stats[$date]['came_back'] += $lastScanDate > $date ? 1 : 0;
            $stats[$date]['signed_in'] += $signedIn ? 1 : 0;
        }

        DB::transaction(function () use ($from, $stats): void {
            SnapGuestDailyStat::query()->where('date', '>=', $from->toDateString())->delete();

            foreach (array_chunk(array_values($stats), 500) as $chunk) {
                SnapGuestDailyStat::query()->fillAndInsert(array_map(function (array $day): array {
                    ksort($day['guests_by_scans']);

                    return $day;
                }, $chunk));
            }
        });
    }

    /**
     * @return array{0: string, 1: string, 2: int, 3: int, 4: bool}
     */
    private function guest(stdClass $guest): array
    {
        $date = fn (mixed $value): string => CarbonImmutable::parse(is_string($value) ? $value : 'now', 'UTC')->toDateString();
        $count = fn (mixed $value): int => is_numeric($value) ? (int) $value : 0;

        return [
            $date($guest->first_scan_at),
            $date($guest->last_scan_at),
            $count($guest->scans),
            $count($guest->failed_scans),
            $guest->owner !== null,
        ];
    }
}
