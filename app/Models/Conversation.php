<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agent Conversation
 *
 * Represents a conversation thread between a user and an AI agent.
 * Contains multiple messages stored in the History model.
 *
 * @property string $id UUID primary key
 * @property int $user_id ID of the user who owns this conversation
 * @property string $title Conversation title/summary
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, History> $messages
 */
final class Conversation extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use HasFactory, HasUuids;

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $table = 'agent_conversations';

    protected $guarded = [];

    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    public function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the conversation.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all messages in this conversation.
     *
     * @return HasMany<History, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(History::class, 'conversation_id')->oldest();
    }
}
