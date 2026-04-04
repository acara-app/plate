<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\MobileSyncDeviceFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $device_name
 * @property string|null $device_identifier
 * @property string|null $encryption_key
 * @property string|null $linking_token
 * @property CarbonInterface|null $token_expires_at
 * @property bool $is_active
 * @property CarbonInterface|null $paired_at
 * @property CarbonInterface|null $last_synced_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read User $user
 */
final class MobileSyncDevice extends Model
{
    /** @use HasFactory<MobileSyncDeviceFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'paired_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'token_expires_at' => 'datetime',
            'encryption_key' => 'encrypted',
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

    public function generateToken(int $expiresInHours = 720): string
    {
        $token = mb_strtoupper(mb_substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));

        $this->update([
            'linking_token' => $token,
            'token_expires_at' => now()->addHours($expiresInHours),
        ]);

        return $token;
    }

    public function markAsPaired(string $deviceName, ?string $deviceIdentifier = null): void
    {
        DB::transaction(function () use ($deviceName, $deviceIdentifier): void {
            if ($deviceIdentifier !== null) {
                self::query()
                    ->where('device_identifier', $deviceIdentifier)
                    ->where('id', '!=', $this->id)
                    ->update(['is_active' => false, 'device_identifier' => null]);
            }

            $this->update([
                'is_active' => true,
                'paired_at' => now(),
                'device_name' => $deviceName,
                'device_identifier' => $deviceIdentifier,
                'linking_token' => null,
                'token_expires_at' => null,
            ]);
        });
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
    protected function paired(Builder $query): void
    {
        $query->whereNotNull('paired_at');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('paired_at')
            ->whereNotNull('linking_token');
    }
}
