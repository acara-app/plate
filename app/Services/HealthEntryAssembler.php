<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MealBreakdownData;
use App\Enums\HealthSyncType;
use App\Enums\MealPhotoRoute;
use App\Enums\MealType;
use App\Models\HealthSyncSample;
use App\Models\MealPhoto;
use Illuminate\Support\Collection;

final readonly class HealthEntryAssembler
{
    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return Collection<int, array<string, mixed>>
     */
    public function assemble(Collection $samples, string $timezone = 'UTC', MealPhotoRoute $photoRoute = MealPhotoRoute::Web): Collection
    {
        $grouped = $samples->groupBy(fn (HealthSyncSample $sample): string => $sample->group_id ?? 'standalone_'.$sample->id);

        $groupIds = $samples->pluck('group_id')->filter()->unique()->values();
        $photoGroupIds = $groupIds->isEmpty() ? collect() : MealPhoto::query()
            ->where('user_id', $samples->first()?->user_id)
            ->whereIn('health_group_id', $groupIds)
            ->pluck('health_group_id')
            ->flip();

        return $grouped->map(function (Collection $group) use ($photoGroupIds, $timezone, $photoRoute): array {
            $ordered = $group->sortBy('id');

            /** @var HealthSyncSample $primary */
            $primary = $ordered->first(fn (HealthSyncSample $sample): bool => ! HealthSyncType::tryFrom($sample->type_identifier)?->isGroupOnly()) ?? $ordered->first();

            $entry = [
                'id' => $primary->id,
                'group_id' => $primary->group_id,
                'glucose_value' => null,
                'glucose_reading_type' => null,
                'measured_at' => $primary->measured_at->toIso8601String(),
                'notes' => null,
                'insulin_units' => null,
                'insulin_type' => null,
                'medication_name' => null,
                'medication_dosage' => null,
                'weight' => null,
                'blood_pressure_systolic' => null,
                'blood_pressure_diastolic' => null,
                'a1c_value' => null,
                'carbs_grams' => null,
                'protein_grams' => null,
                'fat_grams' => null,
                'calories' => null,
                'fiber_grams' => null,
                'exercise_type' => null,
                'exercise_duration_minutes' => null,
                'source' => $primary->entry_source?->value,
                'meal_type' => null,
                'food_analysis' => null,
                'photo_url' => $primary->group_id !== null && $photoGroupIds->has($primary->group_id)
                    ? $photoRoute->urlFor($primary->group_id)
                    : null,
                'created_at' => $primary->created_at->toIso8601String(),
            ];

            foreach ($group as $sample) {
                /** @var HealthSyncSample $sample */
                $metadata = $sample->metadata ?? [];

                if ($sample->notes !== null) {
                    $entry['notes'] = $sample->notes;
                }

                if ($entry['food_analysis'] === null && is_array($metadata['snap_to_track'] ?? null)) {
                    $entry['food_analysis'] = MealBreakdownData::fromMetadata($metadata['snap_to_track'])->toArray();
                }

                if (is_string($metadata['meal_type'] ?? null)) {
                    $entry['meal_type'] = MealType::tryFrom($metadata['meal_type'])?->value;
                }

                $this->mapSampleToEntry($sample, $metadata, $entry);
            }

            if ($entry['meal_type'] === null && $this->hasFood($entry)) {
                $entry['meal_type'] = MealType::forLocalTime($primary->measured_at->toImmutable()->setTimezone($timezone))->value;
            }

            return $entry;
        })->sortByDesc('measured_at')->values();
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function hasFood(array $entry): bool
    {
        return $entry['calories'] !== null || $entry['carbs_grams'] !== null || $entry['protein_grams'] !== null || $entry['fat_grams'] !== null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $entry
     */
    private function mapSampleToEntry(HealthSyncSample $sample, array $metadata, array &$entry): void
    {
        match ($sample->type_identifier) {
            HealthSyncType::BloodGlucose->value => $this->mapGlucose($sample, $metadata, $entry),
            HealthSyncType::Carbohydrates->value => $entry['carbs_grams'] = $sample->value,
            HealthSyncType::Protein->value => $entry['protein_grams'] = $sample->value,
            HealthSyncType::TotalFat->value => $entry['fat_grams'] = $sample->value,
            HealthSyncType::DietaryEnergy->value => $entry['calories'] = (int) $sample->value,
            HealthSyncType::Fiber->value => $entry['fiber_grams'] = $sample->value,
            HealthSyncType::Weight->value => $entry['weight'] = $sample->value,
            HealthSyncType::BloodPressureSystolic->value => $entry['blood_pressure_systolic'] = (int) $sample->value,
            HealthSyncType::BloodPressureDiastolic->value => $entry['blood_pressure_diastolic'] = (int) $sample->value,
            HealthSyncType::A1c->value => $entry['a1c_value'] = $sample->value,
            HealthSyncType::Insulin->value => $this->mapInsulin($sample, $metadata, $entry),
            HealthSyncType::Medication->value, HealthSyncType::MedicationDoseEvent->value => $this->mapMedication($sample, $entry),
            HealthSyncType::ExerciseMinutes->value, HealthSyncType::Workouts->value => $this->mapExercise($sample, $metadata, $entry),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $entry
     */
    private function mapGlucose(HealthSyncSample $sample, array $metadata, array &$entry): void
    {
        // @codeCoverageIgnoreStart
        $entry['glucose_value'] = $sample->value;
        $entry['glucose_reading_type'] = $metadata['glucose_reading_type'] ?? null;
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $entry
     */
    private function mapInsulin(HealthSyncSample $sample, array $metadata, array &$entry): void
    {
        $entry['insulin_units'] = $sample->value;
        $entry['insulin_type'] = $metadata['insulin_type'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function mapMedication(HealthSyncSample $sample, array &$entry): void
    {
        $entry['medication_name'] = $sample->medicationName();
        $entry['medication_dosage'] = $sample->medicationDosage();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $entry
     */
    private function mapExercise(HealthSyncSample $sample, array $metadata, array &$entry): void
    {
        $entry['exercise_duration_minutes'] = (int) $sample->value;
        $entry['exercise_type'] = $metadata['exercise_type']
            ?? HealthSyncType::tryFrom($sample->type_identifier)?->label()
            ?? $sample->type_identifier;
    }
}
