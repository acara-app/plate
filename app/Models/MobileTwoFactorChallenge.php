<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\MobileTwoFactorChallengeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property string $token_hash
 * @property int $user_id
 * @property string $device_identifier
 * @property int $attempts
 * @property CarbonInterface $expires_at
 * @property-read User $user
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 *
 * @codeCoverageIgnore
 */
final class MobileTwoFactorChallenge extends Model
{
    /** @use HasFactory<MobileTwoFactorChallengeFactory> */
    use HasFactory;

    use MassPrunable;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return $this->where('expires_at', '<', now());
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
            'user_id' => 'integer',
            'attempts' => 'integer',
            'expires_at' => 'datetime',
        ];
    }
}
