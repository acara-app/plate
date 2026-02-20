<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Responses\Data\Usage;
use Database\Factories\HistoryFactory;
use Carbon\CarbonInterface;
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
 * @property array<string, mixed> $attachments
 * @property array<ToolCall> $tool_calls
 * @property array<ToolResult> $tool_results
 * @property array{Usage} $usage
 * @property array<string, mixed> $meta
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read Conversation $conversation
 * @property-read User $user
 */
final class History extends Model
{
    /** @use HasFactory<HistoryFactory> */
    use HasFactory, HasUuids;

    protected $table = 'agent_conversation_messages';

    protected $guarded = [];

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
        ];
    }

    /**
     * Get the conversation that owns this message.
     *
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Get the user that owns this message.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
