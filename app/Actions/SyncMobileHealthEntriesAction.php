<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\MobileSync\HealthEntryData;
use App\Enums\HealthEntrySource;
use App\Enums\HealthSyncType;
use App\Exceptions\HealthUnitConversionException;
use App\Models\HealthSyncSample;
use App\Models\MobileSyncDevice;
use App\Models\User;
use App\Services\HealthKitCharacteristicMapper;
use App\Services\HealthMetricUnitConverter;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** @codeCoverageIgnore */
final readonly class SyncMobileHealthEntriesAction
{
    public function __construct(
        private HealthKitCharacteristicMapper $characteristicMapper,
        private HealthMetricUnitConverter $unitConverter,
    ) {}

    /**
     * @param  list<HealthEntryData>  $entries
     * @return array{samples_created: int, samples_updated: int, samples_dropped: int, profile_updated: bool}
     */
    public function handle(User $user, MobileSyncDevice $device, array $entries, ?string $timezone = null): array
    {
        return DB::transaction(function () use ($user, $device, $entries, $timezone): array {
            $profileUpdated = $this->syncUserCharacteristics($user, $entries);
            $sampleCounts = $this->syncSamples($user, $device, $entries, $timezone);

            $device->update(['last_synced_at' => now()]);

            return [
                'samples_created' => $sampleCounts['created'],
                'samples_updated' => $sampleCounts['updated'],
                'samples_dropped' => $sampleCounts['dropped'],
                'profile_updated' => $profileUpdated,
            ];
        });
    }

    /**
     * @param  list<HealthEntryData>  $entries
     * @return array{created: int, updated: int, dropped: int}
     */
    private function syncSamples(User $user, MobileSyncDevice $device, array $entries, ?string $timezone): array
    {
        $syncableEntries = [];
        $bpGroupIds = [];

        foreach ($entries as $entry) {
            $syncType = HealthSyncType::tryFrom($entry->type);

            if ($syncType !== null && $syncType->isUserCharacteristic()) {
                continue;
            }

            if ($syncType === HealthSyncType::BloodPressureSystolic || $syncType === HealthSyncType::BloodPressureDiastolic) {
                $dateKey = Date::parse($entry->date)->toDateTimeString();
                $bpGroupIds[$dateKey] ??= (string) Str::uuid();
            }

            $syncableEntries[] = $entry;
        }

        if ($syncableEntries === []) {
            return ['created' => 0, 'updated' => 0, 'dropped' => 0];
        }

        $cache = $this->preloadSamples($user, $syncableEntries);
        $uuidCache = $this->preloadSamplesByUuid($user, $syncableEntries);

        $created = 0;
        $updated = 0;
        $dropped = 0;

        foreach ($syncableEntries as $entry) {
            $measuredAt = Date::parse($entry->date);
            $endedAt = $entry->ended_at !== null ? Date::parse($entry->ended_at) : null;
            $key = $entry->type.'|'.$measuredAt->toDateTimeString();

            $syncType = HealthSyncType::tryFrom($entry->type);
            $metadata = $entry->metadata;

            if ($syncType !== null) {
                $metadata = $syncType->normalizeMetadata($metadata);
            }

            try {
                $converted = $this->unitConverter->toCanonical(
                    typeIdentifier: $entry->type,
                    value: $entry->value,
                    unit: $entry->unit,
                );
            } catch (HealthUnitConversionException) {
                $dropped++;

                continue;
            }

            $baseAttrs = [
                'value' => $converted['value'],
                'unit' => $converted['canonical_unit'],
                'original_unit' => $converted['original_unit'],
                'measured_at' => $measuredAt,
                'ended_at' => $endedAt,
                'source' => $entry->source,
                'entry_source' => HealthEntrySource::MobileSync,
                'timezone' => $timezone,
                'metadata' => $metadata,
                'sample_uuid' => $entry->sample_uuid,
            ];

            if ($syncType === HealthSyncType::Medication) {
                HealthSyncSample::query()->create([
                    ...$baseAttrs,
                    'user_id' => $user->id,
                    'mobile_sync_device_id' => $device->id,
                    'type_identifier' => HealthSyncType::Medication->value,
                ]);
                $created++;

                continue;
            }

            $existing = $this->findExisting($entry->sample_uuid, $key, $uuidCache, $cache);

            if ($existing instanceof HealthSyncSample) {
                $existing->update($baseAttrs);
                $updated++;

                continue;
            }

            $dateKey = $measuredAt->toDateTimeString();

            $sample = HealthSyncSample::query()->create([
                ...$baseAttrs,
                'user_id' => $user->id,
                'mobile_sync_device_id' => $device->id,
                'type_identifier' => $entry->type,
                'group_id' => $bpGroupIds[$dateKey] ?? null,
            ]);
            $cache[$key] = $sample;

            if ($entry->sample_uuid !== null) {
                $uuidCache[$entry->sample_uuid] = $sample;
            }

            $created++;
        }

        return ['created' => $created, 'updated' => $updated, 'dropped' => $dropped];
    }

    /**
     * @param  array<string, HealthSyncSample>  $uuidCache
     * @param  array<string, HealthSyncSample>  $cache
     */
    private function findExisting(
        ?string $sampleUuid,
        string $typeTimestampKey,
        array &$uuidCache,
        array &$cache,
    ): ?HealthSyncSample {
        if ($sampleUuid !== null && isset($uuidCache[$sampleUuid])) {
            return $uuidCache[$sampleUuid];
        }

        return $cache[$typeTimestampKey] ?? null;
    }

    /**
     * @param  list<HealthEntryData>  $entries
     */
    private function syncUserCharacteristics(User $user, array $entries): bool
    {
        $updateData = [];

        foreach ($entries as $entry) {
            $syncType = HealthSyncType::tryFrom($entry->type);
            if ($syncType === null) {
                continue;
            }

            if (! $syncType->isUserCharacteristic()) {
                continue;
            }

            $updateData = array_merge($updateData, $this->characteristicMapper->map($syncType, $entry->value));
        }

        $updateData = array_filter($updateData, fn (mixed $v): bool => $v !== null);

        if ($updateData === []) {
            return false;
        }

        $user->profile()->firstOrCreate(['user_id' => $user->id])->update($updateData);

        return true;
    }

    /**
     * @param  list<HealthEntryData>  $entries
     * @return array<string, HealthSyncSample>
     */
    private function preloadSamples(User $user, array $entries): array
    {
        $types = collect($entries)->pluck('type')->unique()->values()->all();
        $dates = collect($entries)->map(fn (HealthEntryData $e): string => Date::parse($e->date)->toDateTimeString())->unique()->values()->all();

        $keyed = [];

        foreach (HealthSyncSample::query()->where('user_id', $user->id)->whereIn('type_identifier', $types)->whereIn('measured_at', $dates)->get() as $sample) {
            $keyed[$sample->type_identifier.'|'.$sample->measured_at->toDateTimeString()] = $sample;
        }

        return $keyed;
    }

    /**
     * @param  list<HealthEntryData>  $entries
     * @return array<string, HealthSyncSample>
     */
    private function preloadSamplesByUuid(User $user, array $entries): array
    {
        $uuids = collect($entries)->pluck('sample_uuid')->filter()->unique()->values()->all();

        if ($uuids === []) {
            return [];
        }

        $keyed = [];

        foreach (HealthSyncSample::query()->where('user_id', $user->id)->whereIn('sample_uuid', $uuids)->get() as $sample) {
            if ($sample->sample_uuid !== null) {
                $keyed[$sample->sample_uuid] = $sample;
            }
        }

        return $keyed;
    }
}
