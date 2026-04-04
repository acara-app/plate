<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BloodType;
use App\Enums\GlucoseReadingType;
use App\Enums\HealthEntrySource;
use App\Enums\HealthSyncType;
use App\Enums\Sex;
use App\Models\HealthEntry;
use App\Models\HealthSyncSample;
use App\Models\MobileSyncDevice;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class SyncMobileHealthEntriesAction
{
    /**
     * @param  array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}>  $entries
     * @return array{health_entries_created: int, health_entries_updated: int, samples_created: int, samples_updated: int, profile_updated: bool}
     */
    public function handle(User $user, MobileSyncDevice $device, array $entries, ?string $timezone = null): array
    {
        return DB::transaction(function () use ($user, $device, $entries, $timezone): array {
            $healthEntryCounts = $this->syncHealthEntries($user, $entries);
            $profileUpdated = $this->syncUserCharacteristics($user, $entries);
            $sampleCounts = $this->syncHealthSyncSamples($user, $device, $entries, $timezone);

            $device->update(['last_synced_at' => now()]);

            return [
                'health_entries_created' => $healthEntryCounts['created'],
                'health_entries_updated' => $healthEntryCounts['updated'],
                'samples_created' => $sampleCounts['created'],
                'samples_updated' => $sampleCounts['updated'],
                'profile_updated' => $profileUpdated,
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

        $cache = $this->preloadHealthEntries($user, $entries);

        foreach ($entries as $entry) {
            $syncType = HealthSyncType::tryFrom($entry['type']);

            if ($syncType === HealthSyncType::BloodGlucose) {
                [$wasCreated, $wasUpdated] = $this->syncGlucoseEntry($user, $entry, $cache);
            } elseif ($syncType === HealthSyncType::BloodPressureSystolic || $syncType === HealthSyncType::BloodPressureDiastolic) {
                [$wasCreated, $wasUpdated] = $this->syncBloodPressureEntry($user, $entry, $syncType, $cache);
            } elseif ($syncType?->healthEntryColumn() !== null) {
                [$wasCreated, $wasUpdated] = $this->syncVitalEntry($user, $entry, $syncType, $cache);
            } else {
                continue;
            }

            $created += $wasCreated ? 1 : 0;
            $updated += $wasUpdated ? 1 : 0;
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * @param  array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}>  $entries
     * @return array<string, HealthEntry> Keyed by "sync_type|measured_at"
     */
    private function preloadHealthEntries(User $user, array $entries): array
    {
        $syncTypes = [];
        $dates = [];

        foreach ($entries as $entry) {
            $syncType = HealthSyncType::tryFrom($entry['type']);

            if ($syncType === HealthSyncType::BloodGlucose) {
                $syncTypes[] = HealthSyncType::BloodGlucose->value;
            } elseif ($syncType === HealthSyncType::BloodPressureSystolic || $syncType === HealthSyncType::BloodPressureDiastolic) {
                $syncTypes[] = HealthSyncType::BloodPressure->value;
            } elseif ($syncType?->healthEntryColumn() !== null) {
                $syncTypes[] = $syncType->value;
            } else {
                continue;
            }

            $dates[] = Date::parse($entry['date'])->toDateTimeString();
        }

        if ($syncTypes === []) {
            return [];
        }

        $existing = HealthEntry::query()
            ->where('user_id', $user->id)
            ->whereIn('sync_type', array_unique($syncTypes))
            ->whereIn('measured_at', array_unique($dates))
            ->get();

        $keyed = [];

        foreach ($existing as $entry) {
            /** @var HealthEntry $entry */
            $key = $entry->sync_type.'|'.$entry->measured_at->toDateTimeString();
            $keyed[$key] = $entry;
        }

        return $keyed;
    }

    /**
     * @param  array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}  $entry
     * @param  array<string, HealthEntry>  $cache
     * @return array{bool, bool}
     */
    private function syncGlucoseEntry(User $user, array $entry, array &$cache): array
    {
        /** @var string $date */
        $date = $entry['date'];
        $measuredAt = Date::parse($date);
        $key = HealthSyncType::BloodGlucose->value.'|'.$measuredAt->toDateTimeString();

        /** @var float|int|string $value */
        $value = $entry['value'];

        if (isset($cache[$key])) {
            $cache[$key]->update([
                'glucose_value' => (float) $value,
                'glucose_reading_type' => GlucoseReadingType::Random->value,
                'source' => HealthEntrySource::MobileSync->value,
            ]);

            return [false, true];
        }

        $cache[$key] = HealthEntry::query()->create([
            'user_id' => $user->id,
            'sync_type' => HealthSyncType::BloodGlucose->value,
            'glucose_value' => (float) $value,
            'glucose_reading_type' => GlucoseReadingType::Random->value,
            'measured_at' => $measuredAt,
            'source' => HealthEntrySource::MobileSync->value,
        ]);

        return [true, false];
    }

    /**
     * @param  array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}  $entry
     * @param  array<string, HealthEntry>  $cache
     * @return array{bool, bool}
     */
    private function syncBloodPressureEntry(User $user, array $entry, HealthSyncType $syncType, array &$cache): array
    {
        $column = $syncType === HealthSyncType::BloodPressureSystolic
            ? 'blood_pressure_systolic'
            : 'blood_pressure_diastolic';

        /** @var string $date */
        $date = $entry['date'];
        $measuredAt = Date::parse($date);
        $key = HealthSyncType::BloodPressure->value.'|'.$measuredAt->toDateTimeString();

        /** @var float|int|string $value */
        $value = $entry['value'];

        if (isset($cache[$key])) {
            $cache[$key]->update([
                $column => (int) $value,
                'source' => HealthEntrySource::MobileSync->value,
            ]);

            return [false, true];
        }

        $cache[$key] = HealthEntry::query()->create([
            'user_id' => $user->id,
            'sync_type' => HealthSyncType::BloodPressure->value,
            $column => (int) $value,
            'measured_at' => $measuredAt,
            'source' => HealthEntrySource::MobileSync->value,
        ]);

        return [true, false];
    }

    /**
     * @param  array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}  $entry
     * @param  array<string, HealthEntry>  $cache
     * @return array{bool, bool}
     */
    private function syncVitalEntry(User $user, array $entry, HealthSyncType $syncType, array &$cache): array
    {
        /** @var string $column */
        $column = $syncType->healthEntryColumn();

        /** @var string $date */
        $date = $entry['date'];
        $measuredAt = Date::parse($date);
        $key = $syncType->value.'|'.$measuredAt->toDateTimeString();

        /** @var float|int|string $value */
        $value = $entry['value'];

        if (isset($cache[$key])) {
            $cache[$key]->update([
                $column => (float) $value,
                'source' => HealthEntrySource::MobileSync->value,
            ]);

            return [false, true];
        }

        $data = [
            'user_id' => $user->id,
            'sync_type' => $syncType->value,
            'measured_at' => $measuredAt,
            'source' => HealthEntrySource::MobileSync->value,
            $column => (float) $value,
        ];

        if ($syncType === HealthSyncType::ExerciseMinutes) {
            $data['exercise_type'] = 'exercise';
            $data['exercise_duration_minutes'] = (int) $value;
        } elseif ($syncType === HealthSyncType::Workouts) {
            $data['exercise_type'] = 'workout';
            $data['exercise_duration_minutes'] = (int) $value;
        }

        $cache[$key] = HealthEntry::query()->create($data);

        return [true, false];
    }

    /**
     * @param  array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}>  $entries
     */
    private function syncUserCharacteristics(User $user, array $entries): bool
    {
        /** @var array<string, mixed> $updateData */
        $updateData = [];

        foreach ($entries as $entry) {
            $syncType = HealthSyncType::tryFrom($entry['type']);
            if ($syncType === null) {
                continue;
            }

            if (! $syncType->isUserCharacteristic()) {
                continue;
            }

            /** @var float|int|string $value */
            $value = $entry['value'];

            match ($syncType) {
                HealthSyncType::BiologicalSex => $updateData['sex'] = $this->mapBiologicalSex((int) $value),
                HealthSyncType::DateOfBirth => $updateData = array_merge($updateData, $this->mapDateOfBirth((float) $value)),
                HealthSyncType::BloodType => $updateData['blood_type'] = $this->mapBloodType((int) $value),
                default => null, // @codeCoverageIgnore
            };
        }

        $updateData = array_filter($updateData, fn (mixed $v): bool => $v !== null);

        if ($updateData === []) {
            return false;
        }

        $user->profile()->firstOrCreate(['user_id' => $user->id])->update($updateData);

        return true;
    }

    private function mapBiologicalSex(int $healthKitValue): ?Sex
    {
        return match ($healthKitValue) {
            1 => Sex::Female,
            2 => Sex::Male,
            3 => Sex::Other,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDateOfBirth(float $healthKitValue): array
    {
        $dateString = (string) (int) $healthKitValue;

        if (mb_strlen($dateString) !== 8) {
            return [];
        }

        $date = Date::createFromFormat('Ymd', $dateString);

        if (! $date) {
            return []; // @codeCoverageIgnore
        }

        return [
            'date_of_birth' => $date->startOfDay(),
            'age' => $date->age,
        ];
    }

    private function mapBloodType(int $healthKitValue): ?BloodType
    {
        return match ($healthKitValue) {
            1 => BloodType::APositive,
            2 => BloodType::ANegative,
            3 => BloodType::BPositive,
            4 => BloodType::BNegative,
            5 => BloodType::ABPositive,
            6 => BloodType::ABNegative,
            7 => BloodType::OPositive,
            8 => BloodType::ONegative,
            default => null,
        };
    }

    /**
     * @param  array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}>  $entries
     * @return array{created: int, updated: int}
     */
    private function syncHealthSyncSamples(User $user, MobileSyncDevice $device, array $entries, ?string $timezone): array
    {
        $unmappedEntries = [];

        foreach ($entries as $entry) {
            $syncType = HealthSyncType::tryFrom($entry['type']);

            if ($syncType !== null && $syncType->isMappedToHealthEntry()) {
                continue;
            }

            $unmappedEntries[] = $entry;
        }

        if ($unmappedEntries === []) {
            return ['created' => 0, 'updated' => 0];
        }

        $cache = $this->preloadSamples($user, $unmappedEntries);

        $created = 0;
        $updated = 0;

        foreach ($unmappedEntries as $entry) {
            /** @var string $date */
            $date = $entry['date'];
            $measuredAt = Date::parse($date);
            $key = $entry['type'].'|'.$measuredAt->toDateTimeString();

            /** @var string|null $source */
            $source = $entry['source'] ?? null;

            if (isset($cache[$key])) {
                $cache[$key]->update([
                    'value' => (float) $entry['value'],
                    'unit' => $entry['unit'],
                    'source' => $source,
                    'timezone' => $timezone,
                ]);
                $updated++;
            } else {
                $cache[$key] = HealthSyncSample::query()->create([
                    'user_id' => $user->id,
                    'mobile_sync_device_id' => $device->id,
                    'type_identifier' => $entry['type'],
                    'value' => (float) $entry['value'],
                    'unit' => $entry['unit'],
                    'measured_at' => $measuredAt,
                    'source' => $source,
                    'timezone' => $timezone,
                ]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * @param  array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}>  $entries
     * @return array<string, HealthSyncSample> Keyed by "type_identifier|measured_at"
     */
    private function preloadSamples(User $user, array $entries): array
    {
        /** @var array<string> $types */
        $types = array_unique(array_column($entries, 'type'));

        /** @var array<string> $dates */
        $dates = array_unique(array_map(
            fn (array $entry): string => Date::parse($entry['date'])->toDateTimeString(),
            $entries,
        ));

        $existing = HealthSyncSample::query()
            ->where('user_id', $user->id)
            ->whereIn('type_identifier', $types)
            ->whereIn('measured_at', $dates)
            ->get();

        $keyed = [];

        foreach ($existing as $sample) {
            /** @var HealthSyncSample $sample */
            $key = $sample->type_identifier.'|'.$sample->measured_at->toDateTimeString();
            $keyed[$key] = $sample;
        }

        return $keyed;
    }
}
