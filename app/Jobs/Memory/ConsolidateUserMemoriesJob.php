<?php

declare(strict_types=1);

namespace App\Jobs\Memory;

use App\Services\Memory\MemoryConsolidator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;

final class ConsolidateUserMemoriesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public int $userId,
        public bool $dryRun = false,
        public ?int $daysLookback = null,
        public ?float $threshold = null,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('memory-consolidation')];
    }

    public function handle(MemoryConsolidator $consolidator): void
    {
        $daysLookback = $this->daysLookback ?? (int) config('memory.consolidation.days_lookback', 3); /** @phpstan-ignore cast.int */
        $consolidator->consolidateForUser(
            userId: $this->userId,
            dryRun: $this->dryRun,
            daysLookback: $daysLookback,
            threshold: $this->threshold,
        );
    }
}
