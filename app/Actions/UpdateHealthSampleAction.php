<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\HealthLogData;
use App\Data\ReviewedMealData;
use App\Enums\HealthEntryType;
use App\Models\HealthSyncSample;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class UpdateHealthSampleAction
{
    public function handle(HealthSyncSample $sample, HealthLogData $data): HealthSyncSample
    {
        return DB::transaction(function () use ($sample, $data): HealthSyncSample {
            $newSamples = $data->toSampleArrays();
            $measuredAt = ($data->measuredAt ?? $sample->measured_at)->toImmutable()->utc();
            $groupId = $sample->group_id;

            $metadata = $sample->metadata['snap_to_track'] ?? null;

            if ($data->logType === HealthEntryType::Food && $data->foodItems === null && is_array($metadata) && is_array($metadata['items'] ?? null)) {
                $previous = (new ReviewedMealData(ReviewedMealData::normalizeItems($metadata['items']), $measuredAt))->toHealthLogDataWithMetadata($metadata);

                if ($data->carbsGrams === $previous->carbsGrams && $data->proteinGrams === $previous->proteinGrams && $data->fatGrams === $previous->fatGrams && $data->calories === $previous->calories) {
                    $newSamples = $previous->toSampleArrays();
                }
            }

            if ($groupId !== null) {
                HealthSyncSample::query()
                    ->where('group_id', $groupId)
                    ->where('id', '!=', $sample->id)
                    ->delete();
            }

            $newGroupId = count($newSamples) > 1 ? ($groupId ?? (string) Str::uuid()) : null;

            /** @var array{type_identifier: string, value: float|int, unit: string, metadata?: array<string, mixed>} $first */
            $first = array_shift($newSamples);

            $sample->update([
                'type_identifier' => $first['type_identifier'],
                'value' => $first['value'],
                'unit' => $first['unit'],
                'measured_at' => $measuredAt,
                'metadata' => empty($first['metadata']) ? null : $first['metadata'],
                'notes' => $data->notes,
                'group_id' => $newGroupId,
            ]);

            foreach ($newSamples as $sampleData) {
                HealthSyncSample::query()->create([
                    'user_id' => $sample->user_id,
                    'type_identifier' => $sampleData['type_identifier'],
                    'value' => $sampleData['value'],
                    'unit' => $sampleData['unit'],
                    'measured_at' => $measuredAt,
                    'entry_source' => $sample->entry_source,
                    'metadata' => empty($sampleData['metadata']) ? null : $sampleData['metadata'],
                    'notes' => $data->notes,
                    'group_id' => $newGroupId,
                ]);
            }

            return $sample->refresh();
        });
    }
}
