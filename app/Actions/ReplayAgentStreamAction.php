<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\Streaming\ManagesStreamChunks;
use App\Enums\AgentStreamStatus;
use App\Models\AgentStreamRun;
use App\Support\Streaming\VercelChunkStreamer;
use App\Utilities\ConfigHelper;
use Illuminate\Support\Sleep;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ReplayAgentStreamAction
{
    public function __construct(private ManagesStreamChunks $chunks) {}

    public function isResumable(AgentStreamRun $run, int $fromSequence = -1): bool
    {
        if ($run->expires_at->isPast()) {
            return false;
        }

        if ($run->status->isTerminal()) {
            return $this->chunks->latestSequence($run->id) > $fromSequence;
        }

        return true;
    }

    public function handle(string $runId, int $fromSequence = -1): StreamedResponse
    {
        return response()->stream(function () use ($runId, $fromSequence) {
            $streamer = new VercelChunkStreamer;
            $cursor = $fromSequence;

            $pollMicros = ConfigHelper::int('altani.stream.poll_interval_ms', 400) * 1000;
            $deadline = now()->addSeconds(ConfigHelper::int('altani.stream.max_tail_seconds', 150));
            $stallDeadline = now()->addSeconds(ConfigHelper::int('altani.stream.stall_seconds', 20));

            while (true) {
                foreach ($this->chunks->chunksAfter($runId, $cursor) as $chunk) {
                    $cursor = $chunk->sequence;

                    if (($line = $streamer->line($chunk->vercel)) !== null) {
                        yield $line;
                    }
                }

                if (connection_aborted() !== 0) {
                    return;
                }

                $run = AgentStreamRun::query()->find($runId);

                if (! $run instanceof AgentStreamRun) {
                    break;
                }

                if ($run->status->isTerminal()) {
                    foreach ($this->chunks->chunksAfter($runId, $cursor) as $chunk) {
                        $cursor = $chunk->sequence;

                        if (($line = $streamer->line($chunk->vercel)) !== null) {
                            yield $line;
                        }
                    }

                    break;
                }

                $now = now();

                if ($now->greaterThan($deadline)) {
                    break;
                }

                if ($run->status === AgentStreamStatus::Queued && $now->greaterThan($stallDeadline)) {
                    break;
                }

                Sleep::usleep($pollMicros);
            }

            yield $streamer->trailer();
        }, headers: [
            'Cache-Control' => 'no-cache, no-transform',
            'Content-Type' => 'text/event-stream',
            'x-vercel-ai-ui-message-stream' => 'v1',
        ]);
    }
}
