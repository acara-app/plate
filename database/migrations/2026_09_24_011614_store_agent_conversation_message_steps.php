<?php

declare(strict_types=1);

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Migrations\AiMigration;

return new class extends AiMigration
{
    private const string TABLE = 'agent_conversation_messages';

    public function up(): void
    {
        Schema::connection($this->getConnection())->table(self::TABLE, function (Blueprint $table): void {
            $table->longText('steps')->nullable();
            $table->string('status', 25)->default(MessageStatus::Completed->value);
        });

        $this->query()->where('role', 'user')->update(['steps' => '[]']);

        $this->query()
            ->select('conversation_id')
            ->distinct()
            ->orderBy('conversation_id')
            ->chunk(100, function (Collection $conversations): void {
                foreach ($conversations as $conversation) {
                    if (is_string($conversation->conversation_id)) {
                        $this->backfill($conversation->conversation_id);
                    }
                }
            });

        $this->query()->whereNull('steps')->update(['steps' => '[]']);

        Schema::connection($this->getConnection())->table(self::TABLE, function (Blueprint $table): void {
            $table->longText('steps')->nullable(false)->change();
        });

        Schema::connection($this->getConnection())->table(self::TABLE, function (Blueprint $table): void {
            $table->dropColumn(['tool_calls', 'tool_results', 'approval_state']);
        });

        Schema::connection($this->getConnection())->table(self::TABLE, function (Blueprint $table): void {
            $table->dropIndex('participant_index');
        });

        Schema::connection($this->getConnection())->table(self::TABLE, function (Blueprint $table): void {
            $table->index(['participant_type', 'participant_id', 'agent'], 'participant_index');
        });
    }

    private function backfill(string $conversationId): void
    {
        $rows = $this->query()
            ->where('conversation_id', $conversationId)
            ->where('role', 'assistant')
            ->orderBy('id')
            ->get();

        $results = [];

        foreach ($rows as $row) {
            $results = array_replace($results, $this->entriesById($row->tool_results));
        }

        foreach ($rows as $row) {
            $meta = $this->decoded($row->meta);
            $gated = $this->gatedCalls($meta, $this->decoded($row->approval_state));

            $calls = [];

            foreach ($this->entriesById($row->tool_calls) as $id => $call) {
                if (! array_key_exists($id, $results) && ! array_key_exists($id, $gated)) {
                    continue;
                }

                $calls[] = [
                    ...Arr::except($call, ['reasoning_id', 'reasoning_summary', 'reasoning_encrypted_content']),
                    ...array_key_exists($id, $gated) ? ['approval_reason' => $gated[$id]] : [],
                    ...array_key_exists($id, $results) ? $this->outcome($results[$id]) : [],
                ];
            }

            $content = is_string($row->content) ? $row->content : '';
            $reasoning = is_string($meta['reasoning'] ?? null) ? $meta['reasoning'] : '';

            $steps = $calls !== [] && $content !== ''
                ? [$this->step('', $calls), $this->step($content, [], $reasoning)]
                : [$this->step($content, $calls, $reasoning)];

            unset($meta['provider_steps'], $meta['provider_content_blocks'], $meta['reasoning']);

            $this->query()->where('id', $row->id)->update([
                'steps' => json_encode($steps),
                'meta' => json_encode($meta),
            ]);
        }
    }

    /**
     * @param  array<array-key, mixed>  $meta
     * @param  array<array-key, mixed>  $approvalState
     * @return array<array-key, string|null>
     */
    private function gatedCalls(array $meta, array $approvalState): array
    {
        $stream = is_array($meta['chat_stream'] ?? null) ? $meta['chat_stream'] : [];
        $requested = is_array($stream['approvals'] ?? null) ? $stream['approvals'] : [];
        $pending = is_array($approvalState['pending'] ?? null) ? $approvalState['pending'] : [];

        return array_map(
            fn (mixed $reason): ?string => is_string($reason) ? $reason : null,
            array_replace($requested, $pending),
        );
    }

    /**
     * @param  array<array-key, mixed>  $result
     * @return array<string, mixed>
     */
    private function outcome(array $result): array
    {
        return [
            'result' => $result['result'] ?? null,
            ...array_filter([
                'denied' => $result['denied'] ?? false,
                'failed' => $result['failed'] ?? false,
            ]),
        ];
    }

    /**
     * @param  list<array<array-key, mixed>>  $calls
     * @return array{content: string, tool_calls: list<array<array-key, mixed>>, reasoning: string, replay_blocks: array{}, provider_tool_calls: array{}}
     */
    private function step(string $content, array $calls = [], string $reasoning = ''): array
    {
        return [
            'content' => $content,
            'tool_calls' => $calls,
            'reasoning' => $reasoning,
            'replay_blocks' => [],
            'provider_tool_calls' => [],
        ];
    }

    /**
     * @return array<array-key, array<array-key, mixed>>
     */
    private function entriesById(mixed $json): array
    {
        $entries = [];

        foreach ($this->decoded($json) as $entry) {
            if (is_array($entry) && is_string($entry['id'] ?? null)) {
                $entries[$entry['id']] = $entry;
            }
        }

        return $entries;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decoded(mixed $json): array
    {
        return is_string($json) && is_array($decoded = json_decode($json, true)) ? $decoded : [];
    }

    private function query(): Builder
    {
        return DB::connection($this->getConnection())->table(self::TABLE);
    }
};
