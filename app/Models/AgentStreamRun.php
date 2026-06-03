<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\ActiveStreamData;
use App\Enums\AgentStreamStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $conversation_id
 * @property int $user_id
 * @property string $agent
 * @property string $channel
 * @property string $model
 * @property string|null $prompt
 * @property AgentStreamStatus $status
 * @property string|null $invocation_id
 * @property string|null $assistant_message_id
 * @property string|null $error
 * @property CarbonInterface $expires_at
 * @property CarbonInterface|null $finalized_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Conversation $conversation
 * @property-read Collection<int, AgentStreamChunk> $chunks
 */
final class AgentStreamRun extends Model
{
    use HasFactory;
    use HasUlids;

    protected $guarded = [];

    public function casts(): array
    {
        return [
            'status' => AgentStreamStatus::class,
            'expires_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function toActiveStreamData(): ActiveStreamData
    {
        return new ActiveStreamData(
            runId: $this->id,
            prompt: $this->prompt ?? '',
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return HasMany<AgentStreamChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(AgentStreamChunk::class, 'run_id');
    }

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', [
            AgentStreamStatus::Queued->value,
            AgentStreamStatus::Running->value,
        ]);
    }
}
