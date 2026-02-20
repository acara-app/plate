<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntryType;
use App\Enums\InsulinType;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class HealthLogData extends Data
{
    public function __construct(
        public bool $isHealthData,
        public HealthEntryType $logType,
        public ?float $glucoseValue = null,
        public ?GlucoseReadingType $glucoseReadingType = null,
        public ?GlucoseUnit $glucoseUnit = null,
        public ?int $carbsGrams = null,
        public ?float $insulinUnits = null,
        public ?InsulinType $insulinType = null,
        public ?string $medicationName = null,
        public ?string $medicationDosage = null,
        public ?float $weight = null,
        public ?int $bpSystolic = null,
        public ?int $bpDiastolic = null,
        public ?string $exerciseType = null,
        public ?int $exerciseDurationMinutes = null,
        public ?CarbonInterface $measuredAt = null,
    ) {}

    /**
     * Format the health log data for display confirmation.
     */
    public function formatForDisplay(): string
    {
        return match ($this->logType) {
            HealthEntryType::Glucose => $this->formatGlucoseLog(),
            HealthEntryType::Food => $this->formatFoodLog(),
            HealthEntryType::Insulin => $this->formatInsulinLog(),
            HealthEntryType::Meds => $this->formatMedsLog(),
            HealthEntryType::Vitals => $this->formatVitalsLog(),
            HealthEntryType::Exercise => $this->formatExerciseLog(),
        };
    }

    /**
     * Convert the health log data to a record array for database storage.
     *
     * @return array<string, mixed>
     */
    public function toRecordArray(): array
    {
        return match ($this->logType) {
            HealthEntryType::Glucose => $this->toGlucoseRecordArray(),
            HealthEntryType::Food => $this->toFoodRecordArray(),
            HealthEntryType::Insulin => $this->toInsulinRecordArray(),
            HealthEntryType::Meds => $this->toMedsRecordArray(),
            HealthEntryType::Vitals => $this->toVitalsRecordArray(),
            HealthEntryType::Exercise => $this->toExerciseRecordArray(),
        };
    }

    private function formatGlucoseLog(): string
    {
        $unit = $this->glucoseUnit ?? GlucoseUnit::MgDl;
        $readingType = $this->glucoseReadingType ?? GlucoseReadingType::Random;

        return sprintf('Glucose %s %s (%s)', $this->glucoseValue, $unit->value, $readingType->label());
    }

    private function formatFoodLog(): string
    {
        return sprintf('Food - %sg carbs', $this->carbsGrams);
    }

    private function formatInsulinLog(): string
    {
        $typeLabel = $this->insulinType?->label() ?? 'Bolus';

        return sprintf('Insulin %s units (%s)', $this->insulinUnits, $typeLabel);
    }

    private function formatMedsLog(): string
    {
        $dosage = $this->medicationDosage ?? '';

        return 'Medication - ' . $this->medicationName.($dosage !== '' && $dosage !== '0' ? ' ' . $dosage : '');
    }

    private function formatVitalsLog(): string
    {
        if ($this->weight !== null) {
            return sprintf('Weight %s kg', $this->weight);
        }

        if ($this->bpSystolic !== null && $this->bpDiastolic !== null) {
            return sprintf('Blood Pressure %d/%d', $this->bpSystolic, $this->bpDiastolic);
        }

        return 'Vitals';
    }

    private function formatExerciseLog(): string
    {
        $type = $this->exerciseType ?? 'exercise';

        return sprintf('Exercise - %s min %s', $this->exerciseDurationMinutes, $type);
    }

    /**
     * @return array<string, mixed>
     */
    private function toGlucoseRecordArray(): array
    {
        return [
            'glucose_value' => $this->glucoseValue,
            'glucose_reading_type' => $this->glucoseReadingType?->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toFoodRecordArray(): array
    {
        return [
            'carbs_grams' => $this->carbsGrams,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toInsulinRecordArray(): array
    {
        return [
            'insulin_units' => $this->insulinUnits,
            'insulin_type' => $this->insulinType?->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toMedsRecordArray(): array
    {
        return [
            'medication_name' => $this->medicationName,
            'medication_dosage' => $this->medicationDosage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toVitalsRecordArray(): array
    {
        return [
            'weight' => $this->weight,
            'blood_pressure_systolic' => $this->bpSystolic,
            'blood_pressure_diastolic' => $this->bpDiastolic,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toExerciseRecordArray(): array
    {
        return [
            'exercise_type' => $this->exerciseType,
            'exercise_duration_minutes' => $this->exerciseDurationMinutes,
        ];
    }
}
