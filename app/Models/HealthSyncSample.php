<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\HealthSyncSampleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $mobile_sync_device_id
 * @property string $type_identifier
 * @property float $value
 * @property string $unit
 * @property CarbonInterface $measured_at
 * @property string|null $source
 * @property array<string, mixed>|null $metadata
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read User $user
 * @property-read MobileSyncDevice|null $mobileSyncDevice
 */
final class HealthSyncSample extends Model
{
    /** @use HasFactory<HealthSyncSampleFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mobile_sync_device_id',
        'type_identifier',
        'value',
        'unit',
        'measured_at',
        'source',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'value' => 'float',
            'measured_at' => 'datetime',
            'metadata' => 'array',
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
     * @return BelongsTo<MobileSyncDevice, $this>
     */
    public function mobileSyncDevice(): BelongsTo
    {
        return $this->belongsTo(MobileSyncDevice::class);
    }
}
