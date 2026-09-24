<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\HistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Messages\MessageRole;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string|null $participant_type
 * @property int|null $participant_id
 * @property string $agent
 * @property MessageRole $role
 * @property string $content
 * @property list<array{type?: string, name?: ?string, base64?: string, mime?: ?string}>|null $attachments
 * @property list<array{content: string, tool_calls: list<TStoredToolCall>, reasoning: string, replay_blocks: array<array-key, mixed>, provider_tool_calls: list<array<string, mixed>>}>|null $steps
 * @property MessageStatus|null $status
 * @property array<string, mixed> $usage
 * @property array{chat_stream?: array<string, mixed>, provider?: string|null, ...<string, mixed>}|null $meta
 * @property string|null $summary_id
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read Conversation $conversation
 * @property-read ConversationSummary|null $summary
 * @property-read Model|null $participant
 *
 * @phpstan-type TStoredToolCall array{id: string, name: string, arguments?: array<string, mixed>, result_id?: string|null, thought_signature?: string, approval_reason?: string|null, result?: mixed, denied?: bool, failed?: bool}
 */
#[Table(name: 'agent_conversation_messages')]
final class History extends Model
{
    /** @use HasFactory<HistoryFactory> */
    use HasFactory, HasUuids;

    public const string STREAM_META_KEY = 'chat_stream';

    public const string STREAM_STATUS_SUBMITTED = 'submitted';

    public const string STREAM_STATUS_PENDING = 'pending';

    public const string STREAM_STATUS_COMPLETED = 'completed';

    public const string STREAM_STATUS_CANCELLED = 'cancelled';

    public const string STREAM_STATUS_FAILED = 'failed';

    protected $guarded = [];

    /**
     * @param  array<string, mixed>  $extra
     * @return array{chat_stream: array<string, mixed>}
     */
    public static function streamMeta(string $streamId, string $status, array $extra = []): array
    {
        return [
            self::STREAM_META_KEY => [
                'stream_id' => $streamId,
                'status' => $status,
                ...$extra,
            ],
        ];
    }

    public function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'role' => MessageRole::class,
            'attachments' => 'array',
            'steps' => 'array',
            'status' => MessageStatus::class,
            'usage' => 'array',
            'meta' => 'array',
            'summary_id' => 'string',
        ];
    }

    /**
     * @return list<TStoredToolCall>
     */
    public function toolCalls(): array
    {
        return array_merge(...array_map(
            fn (array $step): array => $step['tool_calls'],
            $this->steps ?? [],
        ));
    }

    /**
     * @return array<string, string|null>
     */
    public function pendingApprovals(): array
    {
        if ($this->status !== MessageStatus::Paused) {
            return [];
        }

        return collect($this->toolCalls())
            ->filter(PendingApproval::isPending(...))
            ->mapWithKeys(fn (array $toolCall): array => [$toolCall['id'] => $toolCall['approval_reason'] ?? null])
            ->all();
    }

    public function hasPendingApprovals(): bool
    {
        return $this->pendingApprovals() !== [];
    }

    /**
     * @return array<string, array{action: string, result?: string|null}>
     */
    public function recordedApprovalDecisions(): array
    {
        $decisions = $this->chatStreamMeta()['approval_decisions'] ?? [];

        if (! is_array($decisions)) {
            return [];
        }

        $recorded = [];

        foreach ($decisions as $toolCallId => $decision) {
            if (! is_array($decision)) {
                continue;
            }

            $action = $decision['action'] ?? null;

            if (! is_string($action)) {
                continue;
            }

            $result = $decision['result'] ?? null;

            $recorded[(string) $toolCallId] = [
                'action' => $action,
                'result' => is_string($result) ? $result : null,
            ];
        }

        return $recorded;
    }

    /**
     * @return array<string, string|null>
     */
    public function requestedApprovals(): array
    {
        $requested = $this->chatStreamMeta()['approvals'] ?? [];

        if (! is_array($requested)) {
            return [];
        }

        $approvals = [];

        foreach ($requested as $toolCallId => $reason) {
            $approvals[(string) $toolCallId] = is_string($reason) ? $reason : null;
        }

        return $approvals;
    }

    public function provider(): ?string
    {
        return $this->meta['provider'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function chatStreamMeta(): array
    {
        return $this->meta[self::STREAM_META_KEY] ?? [];
    }

    public function chatStreamId(): ?string
    {
        // @codeCoverageIgnoreStart
        $streamId = $this->chatStreamMeta()['stream_id'] ?? null;

        return is_string($streamId) ? $streamId : null;
        // @codeCoverageIgnoreEnd
    }

    public function chatStreamStatus(): ?string
    {
        $status = $this->chatStreamMeta()['status'] ?? null;

        return is_string($status) ? $status : null;
    }

    public function belongsToChatStream(string $streamId): bool
    {
        // @codeCoverageIgnoreStart
        return $this->chatStreamId() === $streamId;
    }

    public function isPendingStreamAssistant(): bool
    {
        return $this->role === MessageRole::Assistant
            && $this->chatStreamStatus() === self::STREAM_STATUS_PENDING;
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function participant(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<ConversationSummary, $this>
     */
    public function summary(): BelongsTo
    {
        return $this->belongsTo(ConversationSummary::class, 'summary_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function paused(Builder $query): void
    {
        $query->where('status', MessageStatus::Paused);
    }
}
