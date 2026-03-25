<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GlucoseReadingType;
use App\Enums\HealthEntrySource;
use App\Models\HealthEntry;
use App\Models\HealthSyncSample;
use App\Models\MobileSyncDevice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class SyncMobileHealthEntriesAction
{
    /**
     * @var array<string, string>
     */
    private const array HEALTH_ENTRY_MAPPING = [
        'bloodGlucose' => 'glucose_value',
        'bloodPressureSystolic' => 'blood_pressure_systolic',
        'bloodPressureDiastolic' => 'blood_pressure_diastolic',
        'weight' => 'weight',
        'carbohydrates' => 'carbs_grams',
        'protein' => 'protein_grams',
        'totalFat' => 'fat_grams',
        'dietaryEnergy' => 'calories',
        'exerciseMinutes' => 'exercise_duration_minutes',
        'workouts' => 'exercise_duration_minutes',
    ];

    /**
     * @param  array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}>  $entries
     * @return array{health_entries_created: int, health_entries_updated: int, samples_created: int, samples_updated: int}
     */
    public function handle(User $user, string $deviceIdentifier, array $entries): array
    {
        $device = MobileSyncDevice::query()
            ->where('user_id', $user->id)
            ->where('device_identifier', $deviceIdentifier)
            ->where('is_active', true)
            ->firstOrFail();

        return DB::transaction(function () use ($user, $device, $entries): array {
            $healthEntryCounts = $this->syncHealthEntries($user, $entries);
            $sampleCounts = $this->syncHealthSyncSamples($user, $device, $entries);

            $device->update(['last_synced_at' => now()]);

            return [
                'health_entries_created' => $healthEntryCounts['created'],
                'health_entries_updated' => $healthEntryCounts['updated'],
                'samples_created' => $sampleCounts['created'],
                'samples_updated' => $sampleCounts['updated'],
            ];
        });
    }

    /**
     * @param  array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}>  $entries
     * @return array{created: int, updated: int}
     */
    private function syncHealthEntries(User $user, array $entries): array
    {
        $created = 0;
        $updated = 0;

        foreach ($entries as $entry) {
            $type = $entry['type'];

            if ($type === 'bloodGlucose') {
                [$wasCreated, $wasUpdated] = $this->syncGlucoseEntry($user, $entry);
                $created += $wasCreated ? 1 : 0;
                $updated += $wasUpdated ? 1 : 0;
            } elseif (isset(self::HEALTH_ENTRY_MAPPING[$type])) {
                [$wasCreated, $wasUpdated] = $this->syncVitalEntry($user, $entry);
                $created += $wasCreated ? 1 : 0;
                $updated += $wasUpdated ? 1 : 0;
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * @param  array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}  $entry
     * @return array{bool, bool}
     */
    private function syncGlucoseEntry(User $user, array $entry): array
    {
        /** @var string $date */
        $date = $entry['date'];
        $measuredAt = Carbon::parse($date);

        /** @var float|int|string $value */
        $value = $entry['value'];

        $existing = HealthEntry::query()
            ->where('user_id', $user->id)
            ->where('sync_type', 'bloodGlucose')
            ->where('measured_at', $measuredAt)
            ->first();

        if ($existing !== null) {
            $existing->update([
                'glucose_value' => (float) $value,
                'glucose_reading_type' => GlucoseReadingType::Random->value,
                'source' => HealthEntrySource::MobileSync->value,
            ]);

            return [false, true];
        }

        HealthEntry::create([
            'user_id' => $user->id,
            'sync_type' => 'bloodGlucose',
            'glucose_value' => (float) $value,
            'glucose_reading_type' => GlucoseReadingType::Random->value,
            'measured_at' => $measuredAt,
            'source' => HealthEntrySource::MobileSync->value,
        ]);

        return [true, false];
    }

    /**
     * @param  array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}  $entry
     * @return array{bool, bool}
     */
    private function syncVitalEntry(User $user, array $entry): array
    {
        $type = $entry['type'];
        $column = self::HEALTH_ENTRY_MAPPING[$type];

        /** @var string $date */
        $date = $entry['date'];
        $measuredAt = Carbon::parse($date);

        /** @var float|int|string $value */
        $value = $entry['value'];

        $existing = HealthEntry::query()
            ->where('user_id', $user->id)
            ->where('sync_type', $type)
            ->where('measured_at', $measuredAt)
            ->first();

        if ($existing !== null) {
            $existing->update([
                $column => (float) $value,
                'source' => HealthEntrySource::MobileSync->value,
            ]);

            return [false, true];
        }

        $data = [
            'user_id' => $user->id,
            'sync_type' => $type,
            'measured_at' => $measuredAt,
            'source' => HealthEntrySource::MobileSync->value,
            $column => (float) $value,
        ];

        if ($type === 'exerciseMinutes') {
            $data['exercise_type'] = 'exercise';
            $data['exercise_duration_minutes'] = (int) $value;
        } elseif ($type === 'workouts') {
            $data['exercise_type'] = 'workout';
            $data['exercise_duration_minutes'] = (int) $value;
        }

        HealthEntry::create($data);

        return [true, false];
    }

    /**
     * @param  array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}>  $entries
     * @return array{created: int, updated: int}
     */
    private function syncHealthSyncSamples(User $user, MobileSyncDevice $device, array $entries): array
    {
        $unmappedTypes = $this->getUnmappedTypes($entries);

        if ($unmappedTypes === []) {
            return ['created' => 0, 'updated' => 0];
        }

        $created = 0;
        $updated = 0;

        foreach ($unmappedTypes as $type) {
            foreach ($entries as $entry) {
                if ($entry['type'] !== $type) {
                    continue;
                }

                /** @var string $date */
                $date = $entry['date'];
                $measuredAt = Carbon::parse($date);

                /** @var float|int|string $value */
                $value = $entry['value'];

                /** @var string $unit */
                $unit = $entry['unit'];

                /** @var string|null $source */
                $source = $entry['source'] ?? null;

                $existing = HealthSyncSample::query()
                    ->where('user_id', $user->id)
                    ->where('type_identifier', $type)
                    ->where('measured_at', $measuredAt)
                    ->first();

                if ($existing !== null) {
                    $existing->update([
                        'value' => (float) $value,
                        'unit' => $unit,
                        'source' => $source,
                    ]);
                    $updated++;
                } else {
                    HealthSyncSample::create([
                        'user_id' => $user->id,
                        'mobile_sync_device_id' => $device->id,
                        'type_identifier' => $type,
                        'value' => (float) $value,
                        'unit' => $unit,
                        'measured_at' => $measuredAt,
                        'source' => $source,
                    ]);
                    $created++;
                }
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * @param  array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}>  $entries
     * @return array<string>
     */
    private function getUnmappedTypes(array $entries): array
    {
        $unmapped = [];

        foreach ($entries as $entry) {
            $type = $entry['type'];
            if (! isset(self::HEALTH_ENTRY_MAPPING[$type]) && $type !== 'bloodGlucose') {
                /** @var string $type */
                $unmapped[] = $type;
            }
        }

        /** @var array<string> */
        return array_unique($unmapped);
    }
}
