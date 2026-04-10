<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AggregateHealthDailySamplesAction;
use App\Models\HealthDailyAggregate;
use App\Models\User;
use App\Services\HealthMetricRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class RevalidateHealthAggregatesCommand extends Command
{
    protected $signature = 'health:revalidate-aggregates
                            {--min-version= : Re-aggregate rows with aggregation_version below this value (defaults to current)}
                            {--user= : Limit to a specific user ID}
                            {--since= : Only revalidate dates on or after this date (Y-m-d)}';

    protected $description = 'Re-aggregate stale daily health aggregates after a registry or logic change';

    public function handle(AggregateHealthDailySamplesAction $action): int
    {
        $minVersion = (int) ($this->option('min-version') ?? HealthMetricRegistry::CURRENT_AGGREGATION_VERSION);
        $userId = $this->option('user');
        $since = $this->option('since');

        $query = HealthDailyAggregate::query()
            ->select('user_id', 'local_date')
            ->where(function ($q) use ($minVersion): void {
                $q->where('aggregation_version', '<', $minVersion)
                    ->orWhereNull('aggregation_version');
            })
            ->groupBy('user_id', 'local_date');

        if ($userId !== null) {
            $query->where('user_id', (int) $userId);
        }

        if ($since !== null) {
            $query->where('local_date', '>=', $since);
        }

        $tuples = $query->get();

        if ($tuples->isEmpty()) {
            $this->info('No stale aggregates found.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d (user, date) tuples to revalidate.', $tuples->count()));

        $bar = $this->output->createProgressBar($tuples->count());
        $bar->start();

        $total = 0;

        foreach ($tuples as $tuple) {
            $user = User::query()->find($tuple->user_id);

            if ($user === null) {
                $bar->advance();

                continue;
            }

            $localDate = CarbonImmutable::parse($tuple->local_date);
            $total += $action->handle($user, $localDate);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info(sprintf('Revalidated %d aggregate rows.', $total));

        return self::SUCCESS;
    }
}
