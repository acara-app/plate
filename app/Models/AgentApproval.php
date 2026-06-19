<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\ApprovalCardData;
use App\Enums\AgentApprovalStatus;
use App\Enums\HealthEntryType;
use App\Services\AiTransparency;
use Carbon\CarbonInterface;
use Database\Factories\AgentApprovalFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property string|null $conversation_id
 * @property string $tool_name
 * @property string|null $channel
 * @property AgentApprovalStatus $status
 * @property array<string, mixed> $payload
 * @property string|null $summary
 * @property array<string, mixed>|null $result
 * @property string|null $error
 * @property CarbonInterface $expires_at
 * @property CarbonInterface|null $resolved_at
 * @property CarbonInterface|null $executed_at
 * @property CarbonInterface|null $notified_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Conversation|null $conversation
 */
final class AgentApproval extends Model
{
    /** @use HasFactory<AgentApprovalFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function casts(): array
    {
        return [
            'status' => AgentApprovalStatus::class,
            'payload' => 'encrypted:array',
            'summary' => 'encrypted',
            'result' => 'array',
            'expires_at' => 'datetime',
            'resolved_at' => 'datetime',
            'executed_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function toCardData(): ApprovalCardData
    {
        return new ApprovalCardData(
            status: $this->status->value,
            summary: (string) ($this->summary ?? ''),
            canApprove: $this->status->canApprove(),
            canReject: $this->status->canReject(),
            error: $this->error,
            notice: $this->carbBoundaryNotice(),
        );
    }

    public function claimNotification(): bool
    {
        // @codeCoverageIgnoreStart
        return self::query()
            ->whereKey($this->getKey())
            ->whereNull('notified_at')
            ->update(['notified_at' => now()]) > 0;
        // @codeCoverageIgnoreEnd
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
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', AgentApprovalStatus::Pending);
    }

    /** @param  Builder<self>  $query */
    #[Scope]
    protected function stale(Builder $query): void
    {
        $query->where('status', AgentApprovalStatus::Pending)
            ->wherePast('expires_at');
    }

    private function carbBoundaryNotice(): ?string
    {
        $isFoodEntry = $this->tool_name === 'log_health_entry'
            && ($this->payload['log_type'] ?? null) === HealthEntryType::Food->value;

        return $isFoodEntry ? AiTransparency::carbBoundaryNotice() : null;
    }
}
