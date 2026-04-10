<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AggregateHealthDailySamplesAction;
use App\Jobs\AggregateUserDayJob;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class AggregateHealthDailyCommand extends Command
{
    protected $signature = 'health:aggregate-daily
                            {--date= : Specific date to aggregate (Y-m-d)}
                            {--from= : Start date for range aggregation (Y-m-d)}
                            {--to= : End date for range aggregation (Y-m-d)}
                            {--user_id= : Aggregate for a specific user}';

    protected $description = 'Aggregate raw health samples into daily summaries';

    public function handle(AggregateHealthDailySamplesAction $action): int
    {
        /** @var string|null $dateString */
        $dateString = $this->option('date');
        /** @var string|null $fromString */
        $fromString = $this->option('from');
        /** @var string|null $toString */
        $toString = $this->option('to');
        /** @var string|null $userId */
        $userId = $this->option('user_id');

        if ($fromString !== null || $toString !== null) {
            return $this->aggregateDateRange($action, $fromString, $toString, $userId);
        }

        if ($userId !== null) {
            $date = $dateString !== null
                ? CarbonImmutable::parse($dateString)
                : CarbonImmutable::yesterday();

            return $this->aggregateForUser($action, (int) $userId, $date);
        }

        if ($dateString !== null) {
            $total = $action->handleAllUsers(CarbonImmutable::parse($dateString));
            $this->info(sprintf('Aggregated daily health data for all users on %s: %d metric rows upserted.', $dateString, $total));

            return self::SUCCESS;
        }

        return $this->dispatchPerUserJobs();
    }

    private function dispatchPerUserJobs(): int
    {
        $rangeStart = CarbonImmutable::now()->subDays(2)->startOfDay();
        $rangeEnd = CarbonImmutable::now()->startOfDay();

        $userIds = DB::table('health_sync_samples')
            ->select('user_id')
            ->where('measured_at', '>=', $rangeStart)
            ->where('measured_at', '<', $rangeEnd)
            ->distinct()
            ->pluck('user_id');

        $dispatched = 0;

        foreach ($userIds as $userId) {
            /** @var int $userId */
            dispatch(new AggregateUserDayJob($userId));
            $dispatched++;
        }

        $this->info(sprintf('Dispatched %d aggregation jobs for users with recent samples.', $dispatched));

        return self::SUCCESS;
    }

    private function aggregateForUser(AggregateHealthDailySamplesAction $action, int $userId, CarbonImmutable $date): int
    {
        $user = User::query()->find($userId);

        if ($user === null) {
            $this->error(sprintf('User ID %d not found.', $userId));

            return self::FAILURE;
        }

        $total = $action->handle($user, $date);
        $this->info(sprintf('Aggregated daily health data for user %d on %s: %d metric rows upserted.', $userId, $date->toDateString(), $total));

        return self::SUCCESS;
    }

    private function aggregateDateRange(AggregateHealthDailySamplesAction $action, ?string $fromString, ?string $toString, ?string $userId): int
    {
        if ($fromString === null || $toString === null) {
            $this->error('Both --from and --to are required for range aggregation.');

            return self::FAILURE;
        }

        $from = CarbonImmutable::parse($fromString);
        $to = CarbonImmutable::parse($toString);

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

            $total = $action->handleDateRange($user, $from, $to);
            $this->info(sprintf('Aggregated daily health data for user %s from %s to %s: %d metric rows upserted.', $userId, $fromString, $toString, $total));

            return self::SUCCESS;
        }

        $total = 0;
        $current = $from;

        while ($current->lte($to)) {
            $total += $action->handleAllUsers($current);
            $current = $current->addDay();
        }

        $this->info(sprintf('Aggregated daily health data from %s to %s: %d total metric rows upserted.', $fromString, $toString, $total));

        return self::SUCCESS;
    }
}
