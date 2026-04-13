<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntryType;
use App\Enums\HealthSyncType;
use App\Enums\InsulinType;
use App\Enums\WeightUnit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Spatie\LaravelData\Data;

final class HealthLogData extends Data
{
    public function __construct(
        public bool $isHealthData,
        public HealthEntryType $logType,
        public ?float $glucoseValue = null,
        public ?GlucoseReadingType $glucoseReadingType = null,
        public ?GlucoseUnit $glucoseUnit = null,
        public ?float $carbsGrams = null,
        public ?float $proteinGrams = null,
        public ?float $fatGrams = null,
        public ?int $calories = null,
        public ?float $insulinUnits = null,
        public ?InsulinType $insulinType = null,
        public ?string $medicationName = null,
        public ?string $medicationDosage = null,
        public ?float $weight = null,
        public ?WeightUnit $weightUnit = null,
        public ?int $bpSystolic = null,
        public ?int $bpDiastolic = null,
        public ?float $a1cValue = null,
        public ?string $exerciseType = null,
        public ?int $exerciseDurationMinutes = null,
        public ?CarbonImmutable $measuredAt = null,
        public ?string $notes = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromParsedArray(array $data): self
    {
        return new self(
            isHealthData: (bool) ($data['is_health_data'] ?? false),
            logType: HealthEntryType::tryFrom(self::toNullableString($data['log_type'] ?? null) ?? '') ?? HealthEntryType::Glucose,
            glucoseValue: self::toFloat($data['glucose_value'] ?? null),
            glucoseReadingType: GlucoseReadingType::tryFrom(self::toNullableString($data['glucose_reading_type'] ?? null) ?? ''),
            glucoseUnit: GlucoseUnit::tryFrom(self::toNullableString($data['glucose_unit'] ?? null) ?? ''),
            carbsGrams: self::toFloat($data['carbs_grams'] ?? null),
            proteinGrams: self::toFloat($data['protein_grams'] ?? null),
            fatGrams: self::toFloat($data['fat_grams'] ?? null),
            calories: self::toInt($data['calories'] ?? null),
            insulinUnits: self::toFloat($data['insulin_units'] ?? null),
            insulinType: InsulinType::tryFrom(self::toNullableString($data['insulin_type'] ?? null) ?? ''),
            medicationName: self::toNullableString($data['medication_name'] ?? null),
            medicationDosage: self::toNullableString($data['medication_dosage'] ?? null),
            weight: self::toFloat($data['weight'] ?? null),
            weightUnit: WeightUnit::tryFrom(self::toNullableString($data['weight_unit'] ?? null) ?? ''),
            bpSystolic: self::toInt($data['blood_pressure_systolic'] ?? $data['bp_systolic'] ?? null),
            bpDiastolic: self::toInt($data['blood_pressure_diastolic'] ?? $data['bp_diastolic'] ?? null),
            a1cValue: self::toFloat($data['a1c_value'] ?? null),
            exerciseType: self::toNullableString($data['exercise_type'] ?? null),
            exerciseDurationMinutes: self::toInt($data['exercise_duration_minutes'] ?? null),
            measuredAt: self::toDateTime($data['measured_at'] ?? null),
            notes: self::toNullableString($data['notes'] ?? null),
        );
    }

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

    /**
     * @return array<int, array{type_identifier: string, value: float|int, unit: string, metadata?: array<string, mixed>}>
     */
    public function toSampleArrays(): array
    {
        return match ($this->logType) {
            HealthEntryType::Glucose => $this->toGlucoseSamples(),
            HealthEntryType::Food => $this->toFoodSamples(),
            HealthEntryType::Insulin => $this->toInsulinSamples(),
            HealthEntryType::Meds => $this->toMedsSamples(),
            HealthEntryType::Vitals => $this->toVitalsSamples(),
            HealthEntryType::Exercise => $this->toExerciseSamples(),
        };
    }

    private static function toFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private static function toInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function toNullableString(mixed $value): ?string
    {
        if (in_array($value, [null, 'null', ''], true)) {
            return null;
        }

        return is_string($value) ? $value : (is_scalar($value) ? (string) $value : null);
    }

    private static function toDateTime(mixed $value): ?CarbonImmutable
    {
        $string = self::toNullableString($value);

        return $string !== null ? Date::parse($string) : null;
    }

    private function formatGlucoseLog(): string
    {
        $unit = $this->glucoseUnit ?? GlucoseUnit::MgDl;
        $readingType = $this->glucoseReadingType ?? GlucoseReadingType::Random;

        return sprintf('Glucose %s %s (%s)', $this->glucoseValue, $unit->value, $readingType->label());
    }

    private function formatFoodLog(): string
    {
        $foodName = $this->notes ?? 'Food';
        $parts = [];

        if ($this->carbsGrams !== null) {
            $parts[] = $this->carbsGrams.'g carbs';
        }

        if ($this->proteinGrams !== null) {
            $parts[] = $this->proteinGrams.'g protein';
        }

        if ($this->fatGrams !== null) {
            $parts[] = $this->fatGrams.'g fat';
        }

        if ($this->calories !== null) {
            $parts[] = $this->calories.' kcal';
        }

        $macros = $parts === [] ? '0g carbs' : implode(', ', $parts);

        return sprintf('%s - %s', $foodName, $macros);
    }

    private function formatInsulinLog(): string
    {
        $typeLabel = $this->insulinType?->label() ?? 'Bolus';

        return sprintf('Insulin %s units (%s)', $this->insulinUnits, $typeLabel);
    }

    private function formatMedsLog(): string
    {
        $dosage = $this->medicationDosage ?? '';

        return 'Medication - '.$this->medicationName.($dosage !== '' && $dosage !== '0' ? ' '.$dosage : '');
    }

    private function formatVitalsLog(): string
    {
        if ($this->weight !== null) {
            return sprintf('Weight %s %s', $this->weight, $this->weightUnit->value ?? 'kg');
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
            'protein_grams' => $this->proteinGrams,
            'fat_grams' => $this->fatGrams,
            'calories' => $this->calories,
            'notes' => $this->notes,
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
            'a1c_value' => $this->a1cValue,
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

    /**
     * @return array<int, array{type_identifier: string, value: float|int, unit: string, metadata?: array<string, mixed>}>
     */
    private function toGlucoseSamples(): array
    {
        $glucoseValue = $this->glucoseValue ?? 0.0;

        if ($this->glucoseUnit === GlucoseUnit::MmolL) {
            $glucoseValue = GlucoseUnit::mmolLToMgDl($glucoseValue);
        }

        return [
            [
                'type_identifier' => HealthSyncType::BloodGlucose->value,
                'value' => $glucoseValue,
                'unit' => HealthSyncType::BloodGlucose->unit(),
                'metadata' => array_filter([
                    'glucose_reading_type' => $this->glucoseReadingType?->value,
                ]),
            ],
        ];
    }

    /**
     * @return array<int, array{type_identifier: string, value: float|int, unit: string, metadata?: array<string, mixed>}>
     */
    private function toFoodSamples(): array
    {
        $samples = [];

        if ($this->carbsGrams !== null) {
            $samples[] = [
                'type_identifier' => HealthSyncType::Carbohydrates->value,
                'value' => $this->carbsGrams,
                'unit' => HealthSyncType::Carbohydrates->unit(),
            ];
        }

        if ($this->proteinGrams !== null) {
            $samples[] = [
                'type_identifier' => HealthSyncType::Protein->value,
                'value' => $this->proteinGrams,
                'unit' => HealthSyncType::Protein->unit(),
            ];
        }

        if ($this->fatGrams !== null) {
            $samples[] = [
                'type_identifier' => HealthSyncType::TotalFat->value,
                'value' => $this->fatGrams,
                'unit' => HealthSyncType::TotalFat->unit(),
            ];
        }

        if ($this->calories !== null) {
            $samples[] = [
                'type_identifier' => HealthSyncType::DietaryEnergy->value,
                'value' => $this->calories,
                'unit' => HealthSyncType::DietaryEnergy->unit(),
            ];
        }

        if ($samples === []) {
            $samples[] = [
                'type_identifier' => HealthSyncType::DietaryEnergy->value,
                'value' => 0,
                'unit' => HealthSyncType::DietaryEnergy->unit(),
            ];
        }

        return $samples;
    }

    /**
     * @return array<int, array{type_identifier: string, value: float|int, unit: string, metadata?: array<string, mixed>}>
     */
    private function toInsulinSamples(): array
    {
        return [
            [
                'type_identifier' => HealthSyncType::Insulin->value,
                'value' => $this->insulinUnits ?? 0,
                'unit' => HealthSyncType::Insulin->unit(),
                'metadata' => array_filter([
                    'insulin_type' => $this->insulinType?->value,
                ]),
            ],
        ];
    }

    /**
     * @return array<int, array{type_identifier: string, value: float|int, unit: string, metadata?: array<string, mixed>}>
     */
    private function toMedsSamples(): array
    {
        return [
            [
                'type_identifier' => HealthSyncType::Medication->value,
                'value' => 1,
                'unit' => HealthSyncType::Medication->unit(),
                'metadata' => array_filter([
                    'medication_name' => $this->medicationName,
                    'medication_dosage' => $this->medicationDosage,
                ]),
            ],
        ];
    }

    /**
     * @return array<int, array{type_identifier: string, value: float|int, unit: string, metadata?: array<string, mixed>}>
     */
    private function toVitalsSamples(): array
    {
        $samples = [];

        if ($this->weight !== null) {
            $weightValue = $this->weight;

            if ($this->weightUnit instanceof WeightUnit) {
                $weightValue = $this->weightUnit->toKg($weightValue);
            }

            $samples[] = [
                'type_identifier' => HealthSyncType::Weight->value,
                'value' => round($weightValue, 4),
                'unit' => HealthSyncType::Weight->unit(),
            ];
        }

        if ($this->bpSystolic !== null) {
            $samples[] = [
                'type_identifier' => HealthSyncType::BloodPressureSystolic->value,
                'value' => $this->bpSystolic,
                'unit' => HealthSyncType::BloodPressureSystolic->unit(),
            ];
        }

        if ($this->bpDiastolic !== null) {
            $samples[] = [
                'type_identifier' => HealthSyncType::BloodPressureDiastolic->value,
                'value' => $this->bpDiastolic,
                'unit' => HealthSyncType::BloodPressureDiastolic->unit(),
            ];
        }

        if ($this->a1cValue !== null) {
            $samples[] = [
                'type_identifier' => HealthSyncType::A1c->value,
                'value' => $this->a1cValue,
                'unit' => HealthSyncType::A1c->unit(),
            ];
        }

        return $samples;
    }

    /**
     * @return array<int, array{type_identifier: string, value: float|int, unit: string, metadata?: array<string, mixed>}>
     */
    private function toExerciseSamples(): array
    {
        return [
            [
                'type_identifier' => HealthSyncType::ExerciseMinutes->value,
                'value' => $this->exerciseDurationMinutes ?? 0,
                'unit' => HealthSyncType::ExerciseMinutes->unit(),
                'metadata' => array_filter([
                    'exercise_type' => $this->exerciseType,
                ]),
            ],
        ];
    }
}
