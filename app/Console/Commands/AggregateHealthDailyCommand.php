<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AggregateHealthDailySamplesAction;
use App\Actions\SleepSessionAggregator;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class AggregateHealthDailyCommand extends Command
{
    protected $signature = 'health:aggregate-daily
                            {--date= : Specific UTC date to aggregate (Y-m-d)}
                            {--from= : Start UTC date for range aggregation (Y-m-d)}
                            {--to= : End UTC date for range aggregation (Y-m-d)}
                            {--user_id= : Aggregate for a specific user}
                            {--repair-days=2 : Number of recent UTC days to repair in default mode}';

    protected $description = 'Aggregate raw health and sleep samples into UTC daily summaries';

    public function handle(
        AggregateHealthDailySamplesAction $healthAggregator,
        SleepSessionAggregator $sleepAggregator,
    ): int {
        /** @var string|null $dateString */
        $dateString = $this->option('date');
        /** @var string|null $fromString */
        $fromString = $this->option('from');
        /** @var string|null $toString */
        $toString = $this->option('to');
        /** @var string|null $userId */
        $userId = $this->option('user_id');
        /** @var int|string $repairDaysOption */
        $repairDaysOption = $this->option('repair-days');
        $repairDays = max(1, (int) $repairDaysOption);

        if ($fromString !== null || $toString !== null) {
            return $this->aggregateDateRange($healthAggregator, $sleepAggregator, $fromString, $toString, $userId);
        }

        if ($userId !== null) {
            $date = $dateString !== null
                ? $this->parseUtcDate($dateString)
                : CarbonImmutable::now('UTC')->subDay()->startOfDay();

            return $this->aggregateForUser($healthAggregator, $sleepAggregator, (int) $userId, $date);
        }

        if ($dateString !== null) {
            $date = $this->parseUtcDate($dateString);
            $total = $this->aggregateAllUsersForUtcDate($healthAggregator, $sleepAggregator, $date);

            $this->info(sprintf(
                'Aggregated UTC daily health data for %s: %d metric rows upserted.',
                $date->toDateString(),
                $total,
            ));

            return self::SUCCESS;
        }

        return $this->aggregateRepairWindow($healthAggregator, $sleepAggregator, $repairDays);
    }

    private function aggregateRepairWindow(
        AggregateHealthDailySamplesAction $healthAggregator,
        SleepSessionAggregator $sleepAggregator,
        int $repairDays,
    ): int {
        $from = CarbonImmutable::now('UTC')->subDays($repairDays)->startOfDay();
        $to = CarbonImmutable::now('UTC')->subDay()->startOfDay();

        $total = 0;
        $current = $from;

        while ($current->lte($to)) {
            $total += $this->aggregateAllUsersForUtcDate($healthAggregator, $sleepAggregator, $current);
            $current = $current->addDay();
        }

        $this->info(sprintf(
            'Repaired UTC daily aggregates from %s to %s: %d metric rows upserted.',
            $from->toDateString(),
            $to->toDateString(),
            $total,
        ));

        return self::SUCCESS;
    }

    private function aggregateForUser(
        AggregateHealthDailySamplesAction $healthAggregator,
        SleepSessionAggregator $sleepAggregator,
        int $userId,
        CarbonImmutable $utcDate,
    ): int {
        $user = User::query()->find($userId);

        if ($user === null) {
            $this->error(sprintf('User ID %d not found.', $userId));

            return self::FAILURE;
        }

        $total = $healthAggregator->handle($user, $utcDate);
        $total += $sleepAggregator->handle($user, $utcDate);

        $this->info(sprintf(
            'Aggregated UTC daily health data for user %d on %s: %d metric rows upserted.',
            $userId,
            $utcDate->toDateString(),
            $total,
        ));

        return self::SUCCESS;
    }

    private function aggregateDateRange(
        AggregateHealthDailySamplesAction $healthAggregator,
        SleepSessionAggregator $sleepAggregator,
        ?string $fromString,
        ?string $toString,
        ?string $userId,
    ): int {
        if ($fromString === null || $toString === null) {
            $this->error('Both --from and --to are required for range aggregation.');

            return self::FAILURE;
        }

        $from = $this->parseUtcDate($fromString);
        $to = $this->parseUtcDate($toString);

        if ($from->gt($to)) {
            $this->error('--from must be before --to.');

            return self::FAILURE;
        }

        if ($userId !== null) {
            $user = User::query()->find((int) $userId);

            if ($user === null) {
                $this->error(sprintf('User ID %s not found.', $userId));

                return self::FAILURE;
            }

            $total = 0;
            $current = $from;

            while ($current->lte($to)) {
                $total += $healthAggregator->handle($user, $current);
                $total += $sleepAggregator->handle($user, $current);
                $current = $current->addDay();
            }

            $this->info(sprintf(
                'Aggregated UTC daily health data for user %s from %s to %s: %d metric rows upserted.',
                $userId,
                $from->toDateString(),
                $to->toDateString(),
                $total,
            ));

            return self::SUCCESS;
        }

        $total = 0;
        $current = $from;

        while ($current->lte($to)) {
            $total += $this->aggregateAllUsersForUtcDate($healthAggregator, $sleepAggregator, $current);
            $current = $current->addDay();
        }

        $this->info(sprintf(
            'Aggregated UTC daily health data from %s to %s: %d total metric rows upserted.',
            $from->toDateString(),
            $to->toDateString(),
            $total,
        ));

        return self::SUCCESS;
    }

    private function aggregateAllUsersForUtcDate(
        AggregateHealthDailySamplesAction $healthAggregator,
        SleepSessionAggregator $sleepAggregator,
        CarbonImmutable $utcDate,
    ): int {
        $dayStart = $utcDate->copy()->utc()->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();

        /** @var Collection<int, int> $sampleUserIds */
        $sampleUserIds = DB::table('health_sync_samples')
            ->select('user_id')
            ->where('measured_at', '>=', $dayStart)
            ->where('measured_at', '<', $dayEnd)
            ->distinct()
            ->pluck('user_id');

        /** @var Collection<int, int> $sleepUserIds */
        $sleepUserIds = DB::table('sleep_sessions')
            ->select('user_id')
            ->where('started_at', '<', $dayEnd)
            ->where('ended_at', '>', $dayStart)
            ->distinct()
            ->pluck('user_id');

        /** @var Collection<int, int> $userIds */
        $userIds = $sampleUserIds->concat($sleepUserIds)->unique()->values();

        $total = 0;

        foreach ($userIds as $userId) {
            /** @var User|null $user */
            $user = User::query()->find($userId);

            if ($user === null) {
                continue;
            }

            $total += $healthAggregator->handle($user, $dayStart);
            $total += $sleepAggregator->handle($user, $dayStart);
        }

        return $total;
    }

    private function parseUtcDate(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, 'UTC')->startOfDay();
    }
}
