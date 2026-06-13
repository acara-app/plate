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
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $mobile_sync_device_id
 * @property string $type_identifier
 * @property float $value
 * @property string $unit
 * @property string|null $original_unit
 * @property CarbonInterface $measured_at
 * @property CarbonInterface|null $ended_at
 * @property string|null $source
 * @property HealthEntrySource|null $entry_source
 * @property string|null $timezone
 * @property array<string, mixed>|null $metadata
 * @property string|null $notes
 * @property string|null $group_id
 * @property string|null $sample_uuid
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read User $user
 * @property-read MobileSyncDevice|null $mobileSyncDevice
 */
final class HealthSyncSample extends Model
{
    /** @use HasFactory<HealthSyncSampleFactory> */
    use HasFactory;

    protected $guarded = [];

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
            'fiber', 'sugar', 'saturatedFat', 'monounsaturatedFat', 'polyunsaturatedFat',
            'dietaryCholesterol', 'sodium', 'potassium', 'calcium', 'iron', 'zinc', 'magnesium',
            'phosphorus', 'copper', 'manganese', 'chloride', 'vitaminA', 'vitaminC', 'vitaminD',
            'vitaminE', 'vitaminK', 'vitaminB6', 'vitaminB12', 'folate', 'biotin', 'niacin',
            'pantothenicAcid', 'riboflavin', 'thiamin', 'selenium', 'chromium', 'molybdenum',
            'iodine', 'water', 'caffeine' => 'food',
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

        $matched = self::query()
            ->where('user_id', $userId)
            ->select('type_identifier')
            ->distinct()
            ->get()
            ->map(fn (self $sample): string => $sample->type_identifier)
            ->filter(fn (string $ti): bool => $ti === $type || self::categoryFor($ti) === $type)
            ->values()
            ->all();

        return $matched !== [] ? $matched : [$type];
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'value' => 'float',
            'measured_at' => 'datetime',
            'ended_at' => 'datetime',
            'metadata' => 'array',
            'entry_source' => HealthEntrySource::class,
        ];
    }

    public function medicationName(): ?string
    {
        $metadata = $this->metadata ?? [];
        $name = $metadata['medication_name']
            ?? $metadata['name']
            ?? $metadata['display_text']
            ?? null;

        return is_string($name) ? $name : null;
    }

    public function medicationDosage(): ?string
    {
        $explicit = ($this->metadata ?? [])['medication_dosage'] ?? null;

        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        return $this->composeMedicationDosage();
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
        // @codeCoverageIgnoreStart
        $query->where('type_identifier', $type->value);
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forEntrySource(Builder $query, HealthEntrySource $source): void
    {
        $query->where('entry_source', $source->value);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function excludingGroup(Builder $query, ?string $groupId): void
    {
        if ($groupId === null) {
            return;
        }

        $query->where(
            fn (Builder $inner): Builder => $inner->where('group_id', '!=', $groupId)->orWhereNull('group_id'),
        );
    }

    private function composeMedicationDosage(): ?string
    {
        $rawForm = ($this->metadata ?? [])['form'] ?? null;
        $form = is_string($rawForm) && $rawForm !== '' ? $rawForm : $this->unit;

        if ($form === '') {
            return null;
        }

        $quantity = mb_rtrim(mb_rtrim(number_format($this->value, 4, '.', ''), '0'), '.');

        if ($form === HealthSyncType::Medication->unit()) {
            return (float) $quantity > 1
                ? $quantity.' '.Str::plural($form, (int) ceil((float) $quantity))
                : null;
        }

        return $quantity.' '.$form;
    }
}
