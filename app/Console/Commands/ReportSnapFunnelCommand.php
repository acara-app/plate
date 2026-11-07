<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Billing\BuildSnapFunnelReport;
use App\Data\Billing\SnapFunnelReportData;
use App\Enums\SnapFunnelStage;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Description('Report the ordered Snap to Track funnel, from a selected photo to a confirmed first payment')]
#[Signature('snap:funnel-report
        {--days=7 : Report journeys that started in the last N days}
        {--by= : Split the funnel by device, source, placement or plan}
        {--include-internal : Include staff journeys and Stripe test-mode payments}
        {--definitions : Explain each stage and how it is de-duplicated}
        {--json : Print the report as JSON}')]
final class ReportSnapFunnelCommand extends Command
{
    private const array PATH_TITLES = [
        'scan' => 'Scan',
        'save' => 'Save path (from result viewed)',
        'upgrade' => 'Upgrade path (from offer viewed)',
    ];

    public function handle(BuildSnapFunnelReport $buildReport): int
    {
        if ($this->option('definitions')) {
            $this->table(['Event', 'Stage', 'Definition'], array_map(
                fn (SnapFunnelStage $stage): array => [$stage->value, $stage->label(), $stage->definition()],
                SnapFunnelStage::cases(),
            ));

            return self::SUCCESS;
        }

        $segmentBy = $this->option('by');

        if ($segmentBy !== null && ! in_array($segmentBy, BuildSnapFunnelReport::SEGMENTS, true)) {
            $this->error('Choose --by from: '.implode(', ', BuildSnapFunnelReport::SEGMENTS).'.');

            return self::FAILURE;
        }

        $to = CarbonImmutable::now();
        $report = $buildReport->handle(
            $to->subDays(max(1, (int) $this->option('days'))),
            $to,
            (bool) $this->option('include-internal'),
            is_string($segmentBy) ? $segmentBy : null,
        );

        if ($this->option('json')) {
            $this->line($report->toJson(JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->renderSummary($report);

        foreach ($report->paths as $name => $steps) {
            $this->renderPath(self::PATH_TITLES[$name] ?? $name, $steps);
        }

        if ($report->segmentBy !== null) {
            $this->renderSegments($report);
        }

        $this->table(['Errors and pending', 'Journeys'], array_map(fn (array $row): array => [$row['label'], $row['journeys']], $report->problems));
        $this->table(
            ['Raw event (repeats included, not unique journeys)', 'Events'],
            array_map(fn (string $event, int $count): array => [$event, $count], array_keys($report->rawEvents), $report->rawEvents),
        );
        $this->renderPayments($report);

        $this->line('Notes');
        foreach ($report->notes as $note) {
            $this->line(" - {$note}");
        }

        return self::SUCCESS;
    }

    private function renderSummary(SnapFunnelReportData $report): void
    {
        $from = CarbonImmutable::parse($report->from)->format('Y-m-d H:i');
        $to = CarbonImmutable::parse($report->to)->format('Y-m-d H:i');
        $exclusions = $report->includesInternal ? 'staff journeys and test-mode payments included' : 'staff journeys and test-mode payments excluded';

        $this->info("Snap to Track funnel · journeys started {$from} → {$to} UTC");
        $journeys = Str::plural('journey', $report->journeys);
        $this->line("{$report->journeys} {$journeys} ({$report->openJourneys} still inside the {$report->windowDays}-day window) · {$exclusions}");

        if ($report->ledgerStartedAt === null || CarbonImmutable::parse($report->ledgerStartedAt)->isAfter(CarbonImmutable::parse($report->from))) {
            $this->warn('Part of this period predates the journey ledger, so it has no journey or first-payment data. See the notes.');
        }
    }

    /** @param  list<array{stage: string, label: string, journeys: int}>  $steps */
    private function renderPath(string $title, array $steps): void
    {
        $first = $steps[0]['journeys'] ?? 0;
        $previous = null;
        $rows = [];

        foreach ($steps as $step) {
            $rows[] = [
                $step['label'],
                $step['journeys'],
                $previous === null ? '—' : $this->percent($step['journeys'], $previous),
                $this->percent($step['journeys'], $first),
            ];
            $previous = $step['journeys'];
        }

        $this->table([$title, 'Journeys', 'Of previous', 'Of first step'], $rows);
    }

    private function renderSegments(SnapFunnelReportData $report): void
    {
        $columns = [
            ['scan', SnapFunnelStage::UploadSelected],
            ['scan', SnapFunnelStage::ResultViewed],
            ['upgrade', SnapFunnelStage::OfferViewed],
            ['upgrade', SnapFunnelStage::UpgradeClicked],
            ['upgrade', SnapFunnelStage::CheckoutStarted],
            ['upgrade', SnapFunnelStage::FirstPayment],
            ['save', SnapFunnelStage::MealSaved],
        ];

        $rows = [];
        foreach ($report->segments as $segment => $data) {
            $row = [$segment, $data['journeys']];
            foreach ($columns as [$path, $stage]) {
                $step = collect($data['paths'][$path] ?? [])->firstWhere('stage', $stage->value);
                $row[] = is_array($step) ? $step['journeys'] : 0;
            }

            $rows[] = $row;
        }

        $this->table(
            [ucfirst((string) $report->segmentBy), 'Journeys', ...array_map(fn (array $column): string => $column[1]->label(), $columns)],
            $rows,
        );
    }

    private function renderPayments(SnapFunnelReportData $report): void
    {
        $payments = $report->payments;
        $rows = [
            ['First payments confirmed by Stripe (live)', $payments['live']],
            ['  from a Snap journey', $payments['attributed']],
            ['  from a staff journey', $payments['staff']],
            ['  with no Snap journey', $payments['unattributed']],
            ['Test-mode first payments', $payments['test_mode']],
            ['New subscriptions in billing records', $payments['subscriptions']],
            ['  with a confirmed first payment', $payments['subscriptions_paid']],
        ];

        foreach ($payments['subscriptions_unpaid'] as $status => $count) {
            $rows[] = ["  without one yet · {$status}", $count];
        }

        $this->table(['Payments in this period', 'Count'], $rows);
    }

    private function percent(int $count, int $of): string
    {
        return $of === 0 ? '—' : round($count / $of * 100).'%';
    }
}
