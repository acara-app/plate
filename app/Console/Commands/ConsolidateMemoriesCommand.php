<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Memory\ConsolidateUserMemoriesJob;
use App\Models\Memory;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('memories:consolidate {--days-lookback= : Override lookback window} {--dry-run : Only report what would merge}')]
#[Description('Dispatch consolidation jobs for every user with recent memories.')]
final class ConsolidateMemoriesCommand extends Command
{
    public function handle(): int
    {
        /** @phpstan-ignore cast.int */
        $lookback = (int) ($this->option('days-lookback') ?? config('memory.consolidation.days_lookback', 3));
        $dryRun = (bool) $this->option('dry-run');

        $userIds = Memory::query()
            ->whereNull('consolidated_into')
            ->where('created_at', '>=', now()->subDays($lookback))
            ->distinct()
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            $this->info('No users with recent un-consolidated memories.');

            return self::SUCCESS;
        }

        foreach ($userIds as $userId) {
            dispatch(new ConsolidateUserMemoriesJob(userId: (int) $userId, dryRun: $dryRun, daysLookback: $lookback));
        }

        $this->info(sprintf('Dispatched consolidation jobs for %d users (lookback=%d days, dry_run=%s).', $userIds->count(), $lookback, $dryRun ? 'yes' : 'no'));

        return self::SUCCESS;
    }
}
