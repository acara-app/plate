<?php

declare(strict_types=1);

namespace App\Enums;

use App\DataObjects\MobileSync\BloodGlucoseMetadata;
use App\DataObjects\MobileSync\MedicationDoseEventMetadata;

enum HealthSyncType: string
{
    case BloodGlucose = 'bloodGlucose';
    case BloodPressureSystolic = 'bloodPressureSystolic';
    case BloodPressureDiastolic = 'bloodPressureDiastolic';
    case Weight = 'weight';
    case Carbohydrates = 'carbohydrates';
    case Protein = 'protein';
    case TotalFat = 'totalFat';
    case DietaryEnergy = 'dietaryEnergy';
    case ExerciseMinutes = 'exerciseMinutes';
    case Workouts = 'workouts';
    case A1c = 'a1c';
    case Insulin = 'insulin';
    case Medication = 'medication';
    case MedicationDoseEvent = 'medicationDoseEvent';

    case BiologicalSex = 'biologicalSex';
    case DateOfBirth = 'dateOfBirth';
    case BloodType = 'bloodType';

    case BloodPressure = 'bloodPressure';

    /**
     * @return array<int, string>
     */
    public static function entryTypeValues(): array
    {
        return array_values(array_map(
            fn (self $case): string => $case->value,
            array_filter(self::cases(), fn (self $case): bool => $case->isSyncable()),
        ));
    }

    /**
     * @return array<int, string>
     */
    public static function userCharacteristicValues(): array
    {
        return array_values(array_map(
            fn (self $case): string => $case->value,
            array_filter(self::cases(), fn (self $case): bool => $case->isUserCharacteristic()),
        ));
    }

    public function isSyncable(): bool
    {
        return ! $this->isUserCharacteristic() && $this !== self::BloodPressure;
    }

    public function isUserCharacteristic(): bool
    {
        return in_array($this, [
            self::BiologicalSex,
            self::DateOfBirth,
            self::BloodType,
        ], true);
    }

    public function category(): string
    {
        return match ($this) {
            self::Carbohydrates, self::Protein, self::TotalFat, self::DietaryEnergy => 'food',
            self::BloodGlucose => 'glucose',
            self::Weight, self::BloodPressureSystolic, self::BloodPressureDiastolic, self::BloodPressure, self::A1c => 'vitals',
            self::Insulin, self::Medication, self::MedicationDoseEvent => 'medication',
            self::ExerciseMinutes, self::Workouts => 'exercise',
            self::BiologicalSex, self::DateOfBirth, self::BloodType => 'profile',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::BloodGlucose => 'mg/dL',
            self::BloodPressureSystolic, self::BloodPressureDiastolic, self::BloodPressure => 'mmHg',
            self::Weight => 'kg',
            self::Carbohydrates, self::Protein, self::TotalFat => 'g',
            self::DietaryEnergy => 'kcal',
            self::ExerciseMinutes, self::Workouts => 'min',
            self::A1c => '%',
            self::Insulin => 'IU',
            self::Medication, self::MedicationDoseEvent => 'dose',
            self::BiologicalSex, self::DateOfBirth, self::BloodType => '',
        };
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return match ($this) {
            self::BloodGlucose => BloodGlucoseMetadata::normalize($metadata),
            self::MedicationDoseEvent => MedicationDoseEventMetadata::normalize($metadata),
            default => $metadata,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BloodGlucose => 'Blood Glucose',
            self::BloodPressureSystolic => 'Blood Pressure (Systolic)',
            self::BloodPressureDiastolic => 'Blood Pressure (Diastolic)',
            self::BloodPressure => 'Blood Pressure',
            self::Weight => 'Weight',
            self::Carbohydrates => 'Carbohydrates',
            self::Protein => 'Protein',
            self::TotalFat => 'Total Fat',
            self::DietaryEnergy => 'Calories',
            self::ExerciseMinutes => 'Exercise',
            self::Workouts => 'Workouts',
            self::A1c => 'A1C',
            self::Insulin => 'Insulin',
            self::Medication => 'Medication',
            self::MedicationDoseEvent => 'Medication Dose',
            self::BiologicalSex => 'Biological Sex',
            self::DateOfBirth => 'Date of Birth',
            self::BloodType => 'Blood Type',
        };
    }
}
