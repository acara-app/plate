<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\AgentPayload;
use App\Ai\Agents\AgentRunner;
use App\Contracts\Streaming\ManagesStreamChunks;
use App\Enums\AgentStreamStatus;
use App\Models\AgentStreamChunk;
use App\Models\AgentStreamRun;
use App\Models\History;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Streaming\Events\Error;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Laravel\Ai\Streaming\Events\TextDelta;
use Throwable;

#[Timeout(150)]
#[Tries(1)]
final class StreamAgentRunJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $runId,
        public readonly AgentPayload $payload,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->runId),
        ];
    }

    public function handle(AgentRunner $agentRunner, ManagesStreamChunks $chunks): void
    {
        $run = AgentStreamRun::query()->find($this->runId);

        if (! $run instanceof AgentStreamRun) {
            return;
        }

        Context::add('chat.channel', $run->channel);
        Context::add('chat.conversation_id', $run->conversation_id);

        $user = User::query()->find($this->payload->userId);

        if (! $user instanceof User) {
            $chunks->markRunStatus($this->runId, AgentStreamStatus::Failed, 'User not found.');

            return;
        }

        $run->update(['status' => AgentStreamStatus::Running]);

        $response = $agentRunner->runWithConversation($this->payload, $user, $run->conversation_id);
        $run->update(['invocation_id' => $response->invocationId]);

        $coalesce = (bool) config('altani.stream.coalesce_text_deltas', true);
        $sequence = 0;
        $failed = false;
        $buffer = null;

        foreach ($response as $event) {
            if (! $event instanceof StreamEvent) {
                continue;
            }

            if ($coalesce && $event instanceof TextDelta) {
                if ($buffer !== null && $buffer->messageId !== $event->messageId) {
                    $chunks->append($this->runId, $sequence++, $buffer);
                    $buffer = null;
                }

                if ($buffer === null) {
                    $buffer = $event;
                } else {
                    $buffer->delta .= $event->delta;
                }

                continue;
            }

            if ($buffer !== null) {
                $chunks->append($this->runId, $sequence++, $buffer);
                $buffer = null;
            }

            $chunks->append($this->runId, $sequence++, $event);

            if ($event instanceof Error && ! $event->recoverable) {
                $failed = true;
                $chunks->markRunStatus($this->runId, AgentStreamStatus::Failed, $event->message);
            }
        }

        if ($buffer !== null) {
            $chunks->append($this->runId, $sequence++, $buffer);
        }

        if (! $failed) {
            $this->captureAssistantMessageId($run);
            $chunks->markRunStatus($this->runId, AgentStreamStatus::Completed);
        }
    }

    public function failed(Throwable $exception): void
    {
        $chunks = resolve(ManagesStreamChunks::class);

        $chunks->append($this->runId, $chunks->latestSequence($this->runId) + 1, new Error(
            id: (string) Str::uuid7(),
            type: 'stream_failed',
            message: 'The stream failed.',
            recoverable: false,
            timestamp: now()->getTimestamp(),
        ));

        $this->persistPartialMessage($exception);

        $chunks->markRunStatus($this->runId, AgentStreamStatus::Failed, $exception->getMessage());
    }

    private function captureAssistantMessageId(AgentStreamRun $run): void
    {
        $message = History::query()
            ->where('conversation_id', $run->conversation_id)
            ->where('agent', $run->agent)
            ->where('role', 'assistant')
            ->where('created_at', '>=', $run->created_at)
            ->orderByDesc('id')
            ->first();

        if ($message instanceof History) {
            $run->update(['assistant_message_id' => $message->id]);
        }
    }

    private function persistPartialMessage(Throwable $exception): void
    {
        $run = AgentStreamRun::query()->find($this->runId);

        if (! $run instanceof AgentStreamRun
            || $run->assistant_message_id !== null
            || $this->turnMessageExists($run, 'assistant')) {
            return;
        }

        $partial = AgentStreamChunk::query()
            ->where('run_id', $this->runId)
            ->where('type', 'text_delta')
            ->orderBy('sequence')
            ->get(['payload'])
            ->map(function (AgentStreamChunk $chunk): string {
                $delta = $chunk->payload['delta'] ?? '';

                return is_string($delta) ? $delta : '';
            })
            ->implode('');

        DB::transaction(function () use ($run, $partial, $exception): void {
            if (! $this->turnMessageExists($run, 'user')) {
                History::query()->insert($this->messageRow($run, 'user', $run->prompt ?? '', '[]'));
            }

            if ($partial !== '') {
                $assistantId = (string) Str::uuid7();
                History::query()->insert($this->messageRow(
                    $run,
                    'assistant',
                    $partial,
                    (string) json_encode(['partial' => true, 'error' => Str::limit($exception->getMessage(), 500)]),
                    $assistantId,
                ));
                $run->update(['assistant_message_id' => $assistantId]);
            }
        });
    }

    private function turnMessageExists(AgentStreamRun $run, string $role): bool
    {
        return History::query()
            ->where('conversation_id', $run->conversation_id)
            ->where('agent', $run->agent)
            ->where('role', $role)
            ->where('created_at', '>=', $run->created_at)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function messageRow(AgentStreamRun $run, string $role, string $content, string $meta, ?string $id = null): array
    {
        $now = now();

        return [
            'id' => $id ?? (string) Str::uuid7(),
            'conversation_id' => $run->conversation_id,
            'user_id' => $run->user_id,
            'agent' => $run->agent,
            'role' => $role,
            'content' => $content,
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '[]',
            'meta' => $meta,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
