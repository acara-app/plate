<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\HealthLogData;
use App\Data\ReviewedMealData;
use App\Enums\HealthEntryType;
use App\Enums\MealType;
use App\Models\HealthSyncSample;
use App\Models\MealPhoto;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class UpdateHealthSampleAction
{
    public function handle(HealthSyncSample $sample, HealthLogData $data): HealthSyncSample
    {
        return DB::transaction(function () use ($sample, $data): HealthSyncSample {
            $measuredAt = ($data->measuredAt ?? $sample->measured_at)->toImmutable()->utc();
            $groupId = $sample->group_id;
            $editedTypes = $data->logType->sampleTypeValues();

            $members = $sample->groupMembers();
            $isEdited = fn (HealthSyncSample $member): bool => in_array($member->type_identifier, $editedTypes, true);
            $replaced = $members->filter($isEdited);
            $kept = $members->reject($isEdited);

            $newSamples = $this->newSamples($data, $replaced, $measuredAt);

            HealthSyncSample::query()
                ->whereKey($replaced->modelKeys())
                ->whereKeyNot($sample->id)
                ->delete();

            $newGroupId = $kept->count() + count($newSamples) > 1 ? ($groupId ?? (string) Str::uuid()) : null;

            if ($groupId !== null && $newGroupId === null) {
                MealPhoto::deleteForGroup($sample->user_id, $groupId);
            }

            HealthSyncSample::query()->whereKey($kept->modelKeys())->update([
                'measured_at' => $measuredAt,
                'notes' => $data->notes,
                'group_id' => $newGroupId,
            ]);

            if ($replaced->contains('id', $sample->id)) {
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
            }

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

    /**
     * @param  Collection<int, HealthSyncSample>  $replaced
     * @return array<int, array{type_identifier: string, value: float|int, unit: string, metadata?: array<string, mixed>}>
     */
    private function newSamples(HealthLogData $data, Collection $replaced, CarbonImmutable $measuredAt): array
    {
        if ($data->logType !== HealthEntryType::Food) {
            return $data->toSampleArrays();
        }

        $storedMealType = HealthSyncSample::firstMetadataIn($replaced, 'meal_type');
        $data->mealType ??= MealType::tryFrom(is_string($storedMealType) ? $storedMealType : '');

        $metadata = HealthSyncSample::firstMetadataIn($replaced, 'snap_to_track');

        if ($data->foodItems === null && is_array($metadata) && is_array($metadata['items'] ?? null)) {
            $previous = (new ReviewedMealData(ReviewedMealData::normalizeItems($metadata['items']), $measuredAt, mealType: $data->mealType))
                ->toHealthLogDataWithMetadata($metadata);

            $sameFiber = ! $data->fiberGramsProvided || $data->fiberGrams === $previous->fiberGrams;

            if ($sameFiber && $data->carbsGrams === $previous->carbsGrams && $data->proteinGrams === $previous->proteinGrams && $data->fatGrams === $previous->fatGrams && $data->calories === $previous->calories) {
                return $previous->toSampleArrays();
            }
        }

        return $data->toSampleArrays();
    }
}
