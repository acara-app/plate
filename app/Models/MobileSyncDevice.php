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

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $device_name
 * @property string|null $device_identifier
 * @property string|null $encryption_key
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

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function paired(Builder $query): void
    {
        $query->whereNotNull('paired_at');
    }
}
