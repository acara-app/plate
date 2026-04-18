<?php

declare(strict_types=1);

namespace App\Models;

use App\Utilities\ConfigHelper;
use Carbon\CarbonInterface;
use Database\Factories\ConversationSummaryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $conversation_id
 * @property int $sequence_number
 * @property string|null $previous_summary_id
 * @property string $summary
 * @property array<int, string> $topics
 * @property array<int, string> $key_facts
 * @property array<int, string> $unresolved_threads
 * @property array<int, string> $resolved_threads
 * @property string $start_message_id
 * @property string $end_message_id
 * @property int $message_count
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read Conversation $conversation
 * @property-read ConversationSummary|null $previousSummary
 */
#[Table(name: 'conversation_summaries')]
final class ConversationSummary extends Model
{
    /** @use HasFactory<ConversationSummaryFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public static function getNextSequenceNumber(string $conversationId): int
    {
        $max = self::query()->forConversation($conversationId)->max('sequence_number');

        return is_numeric($max) ? ((int) $max + 1) : 1;
    }

    public static function getLatestForConversation(string $conversationId): ?self
    {
        return self::query()->forConversation($conversationId)
            ->orderByDesc('sequence_number')
            ->first();
    }

    /**
     * @return Collection<int, self>
     */
    public static function getRecentForContext(string $conversationId, ?int $count = null): Collection
    {
        $count ??= ConfigHelper::int('altani.context.recent_summaries', 3);

        return self::query()->forConversation($conversationId)
            ->recent($count)
            ->get()
            ->reverse()
            ->values();
    }

    public function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'topics' => 'array',
            'key_facts' => 'array',
            'unresolved_threads' => 'array',
            'resolved_threads' => 'array',
            'message_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function previousSummary(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_summary_id');
    }

    public function hasUnresolvedThreads(): bool
    {
        return $this->unresolved_threads !== [];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forConversation(Builder $query, string $conversationId): void
    {
        $query->where('conversation_id', $conversationId);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function recent(Builder $query, int $limit = 3): void
    {
        $query->orderByDesc('sequence_number')->limit($limit);
    }
}
