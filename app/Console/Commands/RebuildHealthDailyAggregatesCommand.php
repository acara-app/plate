<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AggregateHealthDailySamplesAction;
use App\Actions\SleepSessionAggregator;
use App\Models\HealthDailyAggregate;
use App\Models\SleepSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class RebuildHealthDailyAggregatesCommand extends Command
{
    protected $signature = 'health:rebuild-daily-aggregates
                            {--from= : Start UTC date (Y-m-d)}
                            {--to= : End UTC date (Y-m-d)}
                            {--user_id= : Restrict rebuild to one user}
                            {--chunk=200 : User chunk size}';

    protected $description = 'Rebuild health_daily_aggregates with UTC-day semantics from raw synced data';

    public function handle(
        AggregateHealthDailySamplesAction $healthAggregator,
        SleepSessionAggregator $sleepAggregator,
    ): int {
        /** @var string|null $fromOption */
        $fromOption = $this->option('from');
        /** @var string|null $toOption */
        $toOption = $this->option('to');
        /** @var string|null $userIdOption */
        $userIdOption = $this->option('user_id');
        /** @var int|string $chunkOption */
        $chunkOption = $this->option('chunk');

        if (($fromOption === null) !== ($toOption === null)) {
            $this->error('Both --from and --to must be provided together.');

            return self::FAILURE;
        }

        $from = $fromOption !== null ? $this->parseUtcDate($fromOption) : null;
        $to = $toOption !== null ? $this->parseUtcDate($toOption) : null;

        if ($from instanceof CarbonImmutable && $to instanceof CarbonImmutable && $from->gt($to)) {
            $this->error('--from must be before or equal to --to.');

            return self::FAILURE;
        }

        $userId = $userIdOption !== null ? (int) $userIdOption : null;
        $chunk = max(1, (int) $chunkOption);

        $this->clearTargetAggregates($userId, $from, $to);

        /** @var Builder<User> $userQuery */
        $userQuery = User::query();

        if ($userId !== null) {
            $userQuery->whereKey($userId);
        } else {
            $userQuery->where(function (Builder $query): void {
                $query->whereHas('healthSyncSamples')
                    ->orWhereHas('sleepSessions');
            });
        }

        $totalUpserted = 0;
        $usersProcessed = 0;

        $userQuery
            ->orderBy('id')
            ->chunkById($chunk, function (Collection $users) use (
                &$totalUpserted,
                &$usersProcessed,
                $from,
                $to,
                $healthAggregator,
                $sleepAggregator,
            ): void {
                foreach ($users as $user) {
                    $usersProcessed++;

                    foreach ($this->resolveUserUtcDates($user, $from, $to) as $utcDate) {
                        $totalUpserted += $healthAggregator->handle($user, $utcDate);
                        $totalUpserted += $sleepAggregator->handle($user, $utcDate);
                    }
                }
            });

        $this->info(sprintf(
            'Rebuilt UTC daily aggregates for %d users: %d metric rows upserted.',
            $usersProcessed,
            $totalUpserted,
        ));

        return self::SUCCESS;
    }

    private function clearTargetAggregates(?int $userId, ?CarbonImmutable $from, ?CarbonImmutable $to): void
    {
        if ($userId === null && ! $from instanceof CarbonImmutable && ! $to instanceof CarbonImmutable) {
            HealthDailyAggregate::query()->truncate();
            $this->info('Cleared all existing health_daily_aggregates rows.');

            return;
        }

        $query = HealthDailyAggregate::query();

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($from instanceof CarbonImmutable && $to instanceof CarbonImmutable) {
            $query->whereBetween(HealthDailyAggregate::UTC_DAY_COLUMN, [$from->toDateString(), $to->toDateString()]);
        }

        /** @var int $deleted */
        $deleted = $query->delete();

        $this->info(sprintf('Cleared %d existing health_daily_aggregates rows in target scope.', $deleted));
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function resolveUserUtcDates(User $user, ?CarbonImmutable $from, ?CarbonImmutable $to): array
    {
        if ($from instanceof CarbonImmutable && $to instanceof CarbonImmutable) {
            $dates = [];
            $current = $from;

            while ($current->lte($to)) {
                $dates[] = $current;
                $current = $current->addDay();
            }

            return $dates;
        }

        $dateMap = [];

        $sampleDates = $user->healthSyncSamples()
            ->selectRaw('DATE(measured_at) as utc_date')
            ->distinct()
            ->pluck('utc_date');

        foreach ($sampleDates as $sampleDate) {
            if (! is_string($sampleDate)) {
                continue; // @codeCoverageIgnore
            }

            $dateMap[$sampleDate] = true;
        }

        /** @var Collection<int, SleepSession> $sleepSessions */
        $sleepSessions = $user->sleepSessions()->get(['started_at', 'ended_at']);

        foreach ($sleepSessions as $session) {
            $current = $session->started_at->copy()->utc()->startOfDay();
            $last = $session->ended_at->copy()->utc()->startOfDay();

            while ($current->lte($last)) {
                $dateMap[$current->toDateString()] = true;
                $current = $current->addDay();
            }
        }

        $dates = array_keys($dateMap);
        sort($dates);

        return array_map(
            static fn (string $date): CarbonImmutable => CarbonImmutable::parse($date, 'UTC')->startOfDay(),
            $dates,
        );
    }

    private function parseUtcDate(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, 'UTC')->startOfDay();
    }
}
