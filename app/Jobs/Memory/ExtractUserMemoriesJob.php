<?php

declare(strict_types=1);

namespace App\Jobs\Memory;

use App\Services\Memory\MemoryExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;

final class ExtractUserMemoriesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(
        public int $userId,
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
        return [new RateLimited('memory-extraction')];
    }

    public function handle(MemoryExtractor $extractor): void
    {
        $extractor->extractForUser($this->userId);
    }
}
