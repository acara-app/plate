<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AiUsageFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int|null $user_id
 * @property-read string $agent
 * @property-read string $model
 * @property-read string $provider
 * @property-read int $prompt_tokens
 * @property-read int $completion_tokens
 * @property-read int $cache_read_input_tokens
 * @property-read int $reasoning_tokens
 * @property-read float $cost
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User|null $user
 */
final class AiUsage extends Model
{
    /** @use HasFactory<AiUsageFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'user_id' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'cache_read_input_tokens' => 'integer',
        'reasoning_tokens' => 'integer',
        'cost' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function totalTokens(): int
    {
        return $this->prompt_tokens + $this->completion_tokens + $this->cache_read_input_tokens + $this->reasoning_tokens;
    }

    /**
     * @param  Builder<AiUsage>  $query
     *
     * @codeCoverageIgnore
     */
    #[Scope]
    protected function forUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    /**
     * @param  Builder<AiUsage>  $query
     *
     * @codeCoverageIgnore
     */
    #[Scope]
    protected function dateRange(Builder $query, ?string $startDate, ?string $endDate): void
    {
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
    }
}
