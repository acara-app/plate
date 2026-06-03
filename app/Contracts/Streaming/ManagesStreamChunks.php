<?php

declare(strict_types=1);

namespace App\Contracts\Streaming;

use App\Enums\AgentStreamStatus;
use App\Models\AgentStreamChunk;
use Laravel\Ai\Streaming\Events\StreamEvent;

interface ManagesStreamChunks
{
    public function append(string $runId, int $sequence, StreamEvent $event): void;

    /**
     * @return iterable<int, AgentStreamChunk>
     */
    public function chunksAfter(string $runId, int $sequence): iterable;

    public function latestSequence(string $runId): int;

    public function markRunStatus(string $runId, AgentStreamStatus $status, ?string $error = null): void;
}
