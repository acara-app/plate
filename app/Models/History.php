<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Ai\Messages\MessageRole;

/**
 * Agent Conversation Message History
 *
 * Stores messages exchanged in AI agent conversations, including user prompts
 * and assistant responses with associated metadata.
 *
 * @property string $id UUID primary key
 * @property string $conversation_id UUID of the parent conversation
 * @property int $user_id ID of the user who owns this message
 * @property string $agent Fully qualified class name of the agent (e.g., App\Ai\Agents\MealPlanGeneratorAgent)
 * @property MessageRole $role Message role (user or assistant)
 * @property string $content The text content of the message
 * @property array $attachments Array of attachments (files, images) associated with user messages
 * @property array<\Laravel\Ai\Responses\Data\ToolCall> $tool_calls Array of tool calls made by the assistant
 * @property array<\Laravel\Ai\Responses\Data\ToolResult> $tool_results Array of tool execution results
 * @property array{\Laravel\Ai\Responses\Data\Usage} $usage Token usage information (prompt_tokens, completion_tokens, etc.)
 * @property array $meta Additional metadata about the message
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read Conversation $conversation
 * @property-read User $user
 */
final class History extends Model
{
    /** @use HasFactory<\Database\Factories\HistoryFactory> */
    use HasFactory;

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
