<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class CaffeineToolSummaryCommand extends Command
{
    protected $signature = 'tools:caffeine:summary
                            {--from= : Start date (Y-m-d), inclusive}
                            {--to= : End date (Y-m-d), inclusive}
                            {--days=14 : Number of recent days when --from/--to are not provided}';

    protected $description = 'Print daily counts of each event for the caffeine calculator tool';

    public function handle(): int
    {
        /** @var string|null $fromOption */
        $fromOption = $this->option('from');
        /** @var string|null $toOption */
        $toOption = $this->option('to');
        /** @var int|string $daysOption */
        $daysOption = $this->option('days');
        $days = max(1, (int) $daysOption);

        $to = $toOption !== null
            ? CarbonImmutable::parse($toOption)->endOfDay()
            : CarbonImmutable::now()->endOfDay();

        $from = $fromOption !== null
            ? CarbonImmutable::parse($fromOption)->startOfDay()
            : $to->copy()->subDays($days - 1)->startOfDay();

        if ($from->gt($to)) {
            $this->error('--from must be before --to.');

            return self::FAILURE;
        }

        /** @var array<int, object{day: string, event_name: string, total: int}> $rows */
        $rows = DB::table('tool_events')
            ->selectRaw('DATE(created_at) AS day, event_name, COUNT(*) AS total')
            ->where('tool_name', 'caffeine-calculator')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day', 'event_name')
            ->orderBy('day')
            ->orderBy('event_name')
            ->get()
            ->all();

        if ($rows === []) {
            $this->info(sprintf(
                'No caffeine-calculator events found between %s and %s.',
                $from->toDateString(),
                $to->toDateString(),
            ));

            return self::SUCCESS;
        }

        /** @var array<string, array<string, int>> $matrix */
        $matrix = [];
        $eventNames = [];

        foreach ($rows as $row) {
            $day = (string) $row->day;
            $event = (string) $row->event_name;
            $total = (int) $row->total;

            $matrix[$day][$event] = $total;
            $eventNames[$event] = true;
        }

        ksort($matrix);
        $events = array_keys($eventNames);
        sort($events);

        $tableRows = [];

        foreach ($matrix as $day => $counts) {
            $tableRow = ['date' => $day];
            $rowTotal = 0;

            foreach ($events as $event) {
                $count = $counts[$event] ?? 0;
                $tableRow[$event] = $count;
                $rowTotal += $count;
            }

            $tableRow['total'] = $rowTotal;
            $tableRows[] = $tableRow;
        }

        $headers = array_merge(['Date'], $events, ['Total']);
        $this->table($headers, $tableRows);

        return self::SUCCESS;
    }
}
