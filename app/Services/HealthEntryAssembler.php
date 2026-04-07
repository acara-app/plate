<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use Illuminate\Support\Collection;

final readonly class HealthEntryAssembler
{
    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return Collection<int, array<string, mixed>>
     */
    public function assemble(Collection $samples): Collection
    {
        $grouped = $samples->groupBy(fn (HealthSyncSample $sample): string => $sample->group_id ?? 'standalone_'.$sample->id);

        return $grouped->map(function (Collection $group): array {
            /** @var HealthSyncSample $primary */
            $primary = $group->sortBy('id')->first();

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
                'exercise_type' => null,
                'exercise_duration_minutes' => null,
                'source' => $primary->entry_source?->value,
                'created_at' => $primary->created_at->toIso8601String(),
            ];

            foreach ($group as $sample) {
                /** @var HealthSyncSample $sample */
                if ($sample->notes !== null) {
                    $entry['notes'] = $sample->notes;
                }

                $this->mapSampleToEntry($sample, $entry);
            }

            return $entry;
        })->sortByDesc('measured_at')->values();
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function mapSampleToEntry(HealthSyncSample $sample, array &$entry): void
    {
        $metadata = $sample->metadata ?? [];

        match ($sample->type_identifier) {
            HealthSyncType::BloodGlucose->value => $this->mapGlucose($sample, $metadata, $entry),
            HealthSyncType::Carbohydrates->value => $entry['carbs_grams'] = $sample->value,
            HealthSyncType::Protein->value => $entry['protein_grams'] = $sample->value,
            HealthSyncType::TotalFat->value => $entry['fat_grams'] = $sample->value,
            HealthSyncType::DietaryEnergy->value => $entry['calories'] = (int) $sample->value,
            HealthSyncType::Weight->value => $entry['weight'] = $sample->value,
            HealthSyncType::BloodPressureSystolic->value => $entry['blood_pressure_systolic'] = (int) $sample->value,
            HealthSyncType::BloodPressureDiastolic->value => $entry['blood_pressure_diastolic'] = (int) $sample->value,
            HealthSyncType::A1c->value => $entry['a1c_value'] = $sample->value,
            HealthSyncType::Insulin->value => $this->mapInsulin($sample, $metadata, $entry),
            HealthSyncType::Medication->value, HealthSyncType::MedicationDoseEvent->value => $this->mapMedication($metadata, $entry),
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
        $entry['glucose_value'] = $sample->value;
        $entry['glucose_reading_type'] = $metadata['glucose_reading_type'] ?? null;
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
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $entry
     */
    private function mapMedication(array $metadata, array &$entry): void
    {
        $entry['medication_name'] = $metadata['medication_name'] ?? null;
        $entry['medication_dosage'] = $metadata['medication_dosage'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $entry
     */
    private function mapExercise(HealthSyncSample $sample, array $metadata, array &$entry): void
    {
        $entry['exercise_duration_minutes'] = (int) $sample->value;
        $entry['exercise_type'] = $metadata['exercise_type'] ?? $sample->type_identifier;
    }
}
