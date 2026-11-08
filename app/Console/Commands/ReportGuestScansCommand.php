<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Billing\BuildGuestScanReport;
use App\Data\Billing\GuestScanReportData;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Report how guests use their free Snap to Track scans: how many scans they use, whether they come back and whether they sign in')]
#[Signature('snap:guest-scans-report
        {--days=90 : Report guests whose first scan was in the last N UTC days}')]
final class ReportGuestScansCommand extends Command
{
    public function handle(BuildGuestScanReport $buildReport): int
    {
        $to = CarbonImmutable::yesterday('UTC');
        $report = $buildReport->handle($to->subDays(max(1, (int) $this->option('days')) - 1), $to);

        $this->info("Guest free scans · guests whose first scan was {$report->from} → {$report->to} (UTC days)");

        if ($report->guests === 0) {
            $this->line('No guest scans in this period.');

            return self::SUCCESS;
        }

        $this->line("{$report->guests} guests · {$report->scans} successful scans · {$report->failedScans} failed ({$this->percent($report->failedScans, $report->scans + $report->failedScans)})");
        $this->table(['Guests', 'Count', 'Share'], $this->rows($report));

        $this->line('Notes');

        foreach ($this->notes($report) as $note) {
            $this->line(" - {$note}");
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array{0: string, 1: int, 2: string}>
     */
    private function rows(GuestScanReportData $report): array
    {
        $rows = [];

        foreach ($report->guestsByScans as $scans => $guests) {
            $label = match ($scans) {
                0 => 'No successful scan',
                1 => '1 successful scan',
                default => "{$scans} successful scans",
            };
            $rows[] = [$label, $guests, $this->percent($guests, $report->guests)];
        }

        $rows[] = ['Came back on a later day', $report->cameBack, $this->percent($report->cameBack, $report->guests)];
        $rows[] = ['Signed up or logged in afterwards', $report->signedIn, $this->percent($report->signedIn, $report->guests)];

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function notes(GuestScanReportData $report): array
    {
        return [
            'A guest is one browser, identified by its photo_guest cookie. Clearing cookies or switching devices counts as a new guest.',
            "Each guest counts on the UTC day of their first scan. Their later scans and sign-ins update that day until their scan records are deleted {$report->retentionDays} days after that first scan, once the cookie has expired. The day's totals are kept after that.",
            'Guests could scan once for free until 2026-10-24, and twice from then on.',
        ];
    }

    private function percent(int $count, int $of): string
    {
        return $of === 0 ? '—' : round($count / $of * 100).'%';
    }
}
