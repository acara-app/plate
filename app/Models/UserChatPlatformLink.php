<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChatPlatform;
use Carbon\CarbonInterface;
use Database\Factories\UserChatPlatformLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property ChatPlatform $platform
 * @property string|null $platform_user_id
 * @property string|null $platform_chat_id
 * @property string|null $conversation_id
 * @property string|null $linking_token
 * @property CarbonInterface|null $token_expires_at
 * @property bool $is_active
 * @property CarbonInterface|null $linked_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read User|null $user
 */
final class UserChatPlatformLink extends Model
{
    /** @use HasFactory<UserChatPlatformLinkFactory> */
    use HasFactory;

    protected $guarded = [];

    public function casts(): array
    {
        return [
            'platform' => ChatPlatform::class,
            'is_active' => 'boolean',
            'linked_at' => 'datetime',
            'token_expires_at' => 'datetime',
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

    public function isLinked(): bool
    {
        return $this->user_id !== null && $this->linked_at !== null && $this->is_active;
    }

    public function isTokenValid(): bool
    {
        if ($this->linking_token === null || $this->token_expires_at === null) {
            return false;
        }

        return $this->token_expires_at->isFuture();
    }

    public function generateToken(int $expiresInHours = 24): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $token = '';

        for ($i = 0; $i < 8; $i++) {
            $token .= $alphabet[random_int(0, 35)];
        }

        $this->update([
            'linking_token' => $token,
            'token_expires_at' => now()->addHours($expiresInHours),
        ]);

        return $token;
    }

    public function markAsLinked(User $user, ?string $platformUserId = null): void
    {
        $this->update([
            'user_id' => $user->id,
            'platform_user_id' => $platformUserId ?? $this->platform_user_id,
            'is_active' => true,
            'linked_at' => now(),
            'linking_token' => null,
            'token_expires_at' => null,
        ]);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forPlatformUser(Builder $query, ChatPlatform $platform, string $platformUserId): void
    {
        $query->where('platform', $platform)
            ->where('platform_user_id', $platformUserId);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function linked(Builder $query): void
    {
        $query->whereNotNull('user_id')
            ->whereNotNull('linked_at')
            ->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function pendingLink(Builder $query): void
    {
        $query->whereNotNull('linking_token')
            ->where('token_expires_at', '>', now())
            ->whereNull('linked_at');
    }
}
