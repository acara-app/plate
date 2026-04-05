<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthEntrySource;
use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\MobileSyncDevice;
use App\Models\User;
use App\Services\HealthKitCharacteristicMapper;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class SyncMobileHealthEntriesAction
{
    public function __construct(
        private HealthKitCharacteristicMapper $characteristicMapper,
    ) {}

    /**
     * @param  array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}>  $entries
     * @return array{samples_created: int, samples_updated: int, profile_updated: bool}
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
                'profile_updated' => $profileUpdated,
            ];
        });
    }

    /**
     * @param  array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}>  $entries
     * @return array{created: int, updated: int}
     */
    private function syncSamples(User $user, MobileSyncDevice $device, array $entries, ?string $timezone): array
    {
        $syncableEntries = [];
        $bpPairs = [];

        foreach ($entries as $entry) {
            $syncType = HealthSyncType::tryFrom($entry['type']);

            if ($syncType !== null && $syncType->isUserCharacteristic()) {
                continue;
            }

            if ($syncType === HealthSyncType::BloodPressureSystolic || $syncType === HealthSyncType::BloodPressureDiastolic) {
                $date = Date::parse($entry['date'])->toDateTimeString();
                $bpPairs[$date] ??= (string) Str::uuid();
                $entry['_group_id'] = $bpPairs[$date];
            }

            $syncableEntries[] = $entry;
        }

        if ($syncableEntries === []) {
            return ['created' => 0, 'updated' => 0];
        }

        $cache = $this->preloadSamples($user, $syncableEntries);

        $created = 0;
        $updated = 0;

        foreach ($syncableEntries as $entry) {
            /** @var string $date */
            $date = $entry['date'];
            $measuredAt = Date::parse($date);
            $key = $entry['type'].'|'.$measuredAt->toDateTimeString();

            /** @var string|null $source */
            $source = $entry['source'] ?? null;

            $metadata = null;
            $syncType = HealthSyncType::tryFrom($entry['type']);

            if ($syncType === HealthSyncType::BloodGlucose) {
                $metadata = ['glucose_reading_type' => 'random'];
            }

            if (isset($cache[$key])) {
                $cache[$key]->update([
                    'value' => (float) $entry['value'],
                    'unit' => $entry['unit'],
                    'source' => $source,
                    'timezone' => $timezone,
                    'metadata' => $metadata,
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
                    'entry_source' => HealthEntrySource::MobileSync,
                    'timezone' => $timezone,
                    'metadata' => $metadata,
                    'group_id' => $entry['_group_id'] ?? null,
                ]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated];
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

            $updateData = array_merge($updateData, $this->characteristicMapper->map($syncType, $value));
        }

        $updateData = array_filter($updateData, fn (mixed $v): bool => $v !== null);

        if ($updateData === []) {
            return false;
        }

        $user->profile()->firstOrCreate(['user_id' => $user->id])->update($updateData);

        return true;
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
