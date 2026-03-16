<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\UserTelegramChatFactory;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $telegraph_chat_id
 * @property string|null $linking_token
 * @property CarbonInterface|null $token_expires_at
 * @property bool $is_active
 * @property CarbonInterface|null $linked_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read User $user
 * @property-read TelegraphChat $telegraphChat
 */
final class UserTelegramChat extends Model
{
    /** @use HasFactory<UserTelegramChatFactory> */
    use HasFactory;

    protected $guarded = [];

    public function casts(): array
    {
        return [
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

    /**
     * @return BelongsTo<TelegraphChat, $this>
     */
    public function telegraphChat(): BelongsTo
    {
        return $this->belongsTo(TelegraphChat::class);
    }

    public function isTokenValid(): bool
    {
        if ($this->linking_token === null) {
            return false;
        }

        if ($this->token_expires_at === null) {
            return false;
        }

        return $this->token_expires_at->isFuture();
    }

    public function markAsLinked(): void
    {
        $this->update([
            'is_active' => true,
            'linked_at' => now(),
            'linking_token' => null,
            'token_expires_at' => null,
        ]);
    }

    public function generateToken(int $expiresInHours = 24): string
    {
        $token = mb_strtoupper(mb_substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));

        $this->update([
            'linking_token' => $token,
            'token_expires_at' => now()->addHours($expiresInHours),
        ]);

        return $token;
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
        $query->whereNotNull('telegraph_chat_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('telegraph_chat_id')
            ->whereNotNull('linking_token');
    }
}
