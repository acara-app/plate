<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HealthEntrySource;
use App\Enums\HealthSyncType;
use Carbon\CarbonInterface;
use Database\Factories\HealthSyncSampleFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
 * @property HealthEntrySource|null $entry_source
 * @property string|null $timezone
 * @property array<string, mixed>|null $metadata
 * @property string|null $notes
 * @property string|null $group_id
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
        'entry_source',
        'timezone',
        'metadata',
        'notes',
        'group_id',
    ];

    public static function categoryFor(string $typeIdentifier): string
    {
        $syncType = HealthSyncType::tryFrom($typeIdentifier);

        if ($syncType !== null) {
            return $syncType->category();
        }

        return match ($typeIdentifier) {
            'heartRate', 'restingHeartRate', 'walkingHeartRateAverage', 'heartRateVariability' => 'heart_rate',
            'stepCount' => 'steps',
            'activeEnergy', 'basalEnergyBurned' => 'active_energy',
            'walkingRunningDistance' => 'distance',
            'flightsClimbed' => 'flights_climbed',
            'standMinutes', 'standHours' => 'stand_time',
            'walkingSpeed', 'walkingStepLength', 'walkingDoubleSupportPercentage', 'walkingAsymmetry' => 'mobility',
            'environmentalAudioExposure' => 'environment',
            default => 'other',
        };
    }

    /**
     * @return array<int, string>|null null means no filter
     */
    public static function resolveTypeFilter(string $type, int $userId): ?array
    {
        if ($type === 'all') {
            return null;
        }

        $existsAsRaw = self::query()
            ->where('user_id', $userId)
            ->where('type_identifier', $type)
            ->exists();

        if ($existsAsRaw) {
            return [$type];
        }

        $userTypes = self::query()
            ->where('user_id', $userId)
            ->select('type_identifier')
            ->distinct()
            ->pluck('type_identifier')
            ->filter(fn (string $ti): bool => self::categoryFor($ti) === $type)
            ->values()
            ->all();

        return $userTypes !== [] ? $userTypes : [$type];
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'value' => 'float',
            'measured_at' => 'datetime',
            'metadata' => 'array',
            'entry_source' => HealthEntrySource::class,
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

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ofType(Builder $query, HealthSyncType $type): void
    {
        $query->where('type_identifier', $type->value);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forEntrySource(Builder $query, HealthEntrySource $source): void
    {
        $query->where('entry_source', $source->value);
    }
}
