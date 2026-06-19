<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\HistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Ai\Messages\MessageRole;

/**
 * @property string $id
 * @property string $conversation_id
 * @property int $user_id
 * @property string $agent
 * @property MessageRole $role
 * @property string $content
 * @property list<array{type?: string, name?: ?string, base64?: string, mime?: ?string}>|null $attachments
 * @property list<array{id: string, name: string, arguments?: array<string, mixed>|null, result_id?: string|null, reasoning_id?: string|null, reasoning_summary?: array<int|string, mixed>|null}>|null $tool_calls
 * @property list<array{id: string, name: string, arguments?: array<string, mixed>|null, result?: mixed, result_id?: string|null}>|null $tool_results
 * @property array<string, mixed> $usage
 * @property array{chat_stream?: array<string, mixed>, ...<string, mixed>}|null $meta
 * @property string|null $summary_id
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read Conversation $conversation
 * @property-read ConversationSummary|null $summary
 * @property-read User $user
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
            'tool_calls' => 'array',
            'tool_results' => 'array',
            'usage' => 'array',
            'meta' => 'array',
            'summary_id' => 'string',
        ];
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ConversationSummary, $this>
     */
    public function summary(): BelongsTo
    {
        return $this->belongsTo(ConversationSummary::class, 'summary_id');
    }
}
