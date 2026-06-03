<?php

declare(strict_types=1);

namespace App\Services\Streaming;

use App\Contracts\Streaming\ManagesStreamChunks;
use App\Enums\AgentStreamStatus;
use App\Models\AgentStreamChunk;
use App\Models\AgentStreamRun;
use Laravel\Ai\Streaming\Events\StreamEvent;

final class DatabaseStreamChunkStore implements ManagesStreamChunks
{
    public function append(string $runId, int $sequence, StreamEvent $event): void
    {
        AgentStreamChunk::query()->create([
            'run_id' => $runId,
            'sequence' => $sequence,
            'type' => $event->type(),
            'payload' => json_decode((string) $event, true),
            'vercel' => $event->toVercelProtocolArray(),
        ]);
    }

    public function chunksAfter(string $runId, int $sequence): iterable
    {
        return AgentStreamChunk::query()
            ->where('run_id', $runId)
            ->where('sequence', '>', $sequence)
            ->orderBy('sequence')
            ->get();
    }

    public function latestSequence(string $runId): int
    {
        $max = AgentStreamChunk::query()
            ->where('run_id', $runId)
            ->max('sequence');

        return is_numeric($max) ? (int) $max : -1;
    }

    public function markRunStatus(string $runId, AgentStreamStatus $status, ?string $error = null): void
    {
        AgentStreamRun::query()
            ->whereKey($runId)
            ->update([
                'status' => $status->value,
                'error' => $error,
                'finalized_at' => $status->isTerminal() ? now() : null,
                'updated_at' => now(),
            ]);
    }
}
