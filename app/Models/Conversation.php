<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id UUID primary key
 * @property int $user_id ID of the user who owns this conversation
 * @property string $title Conversation title/summary
 * @property CarbonInterface|null $summarization_dispatched_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Collection<int, History> $messages
 * @property-read Collection<int, ConversationSummary> $summaries
 */
#[Table(name: 'agent_conversations')]
final class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory, HasUuids;

    /**
     * @var bool
     */
    public $incrementing = false;

    protected $guarded = [];

    public function casts(): array
    {
        return [
            'id' => 'string',
            'summarization_dispatched_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<History, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(History::class, 'conversation_id')->oldest();
    }

    /**
     * @return HasMany<ConversationSummary, $this>
     */
    public function summaries(): HasMany
    {
        return $this->hasMany(ConversationSummary::class, 'conversation_id');
    }
}
