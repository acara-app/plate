<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnalysisDraftSource;
use Carbon\CarbonInterface;
use Database\Factories\AnalysisDraftFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property string $token_hash
 * @property int $schema_version
 * @property AnalysisDraftSource $source
 * @property array<string, mixed> $payload
 * @property int|null $user_id
 * @property CarbonInterface|null $claimed_at
 * @property CarbonInterface|null $consumed_at
 * @property string|null $health_group_id
 * @property CarbonInterface $expires_at
 * @property-read User|null $user
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class AnalysisDraft extends Model
{
    /** @use HasFactory<AnalysisDraftFactory> */
    use HasFactory;

    use MassPrunable;

    public const int SCHEMA_VERSION = 1;

    public const int TTL_MINUTES = 60;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function ageBand(): string
    {
        $minutes = (int) $this->created_at->diffInMinutes(now());

        return match (true) {
            $minutes < 5 => 'lt_5m',
            $minutes < 15 => 'lt_15m',
            $minutes < 30 => 'lt_30m',
            $minutes < 60 => 'lt_60m',
            default => 'gte_60m',
        };
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return $this->where('created_at', '<', now()->subDay());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'schema_version' => 'integer',
            'source' => AnalysisDraftSource::class,
            'payload' => 'array',
            'user_id' => 'integer',
            'claimed_at' => 'datetime',
            'consumed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
