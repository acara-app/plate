<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AgentStreamChunk;
use App\Models\AgentStreamRun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Description('Delete agent stream runs (and their chunks) that have passed their resume TTL.')]
#[Signature('streams:prune-expired')]
final class PruneExpiredStreamRunsCommand extends Command
{
    public function handle(): int
    {
        $pruned = 0;

        AgentStreamRun::query()
            ->whereNowOrPast('expires_at')
            ->chunkById(100, function (Collection $expired) use (&$pruned): void {
                $ids = $expired->modelKeys();

                AgentStreamChunk::query()->whereIn('run_id', $ids)->delete();
                AgentStreamRun::query()->whereKey($ids)->delete();

                $pruned += count($ids);
            });

        $this->info($pruned === 0
            ? 'No expired stream runs to prune.'
            : sprintf('Pruned %d expired stream run(s).', $pruned));

        return self::SUCCESS;
    }
}
