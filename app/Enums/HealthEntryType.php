<?php

declare(strict_types=1);

namespace App\Enums;

use App\Actions\RecordWaterIntakeAction;

enum HealthEntryType: string
{
    case Glucose = 'glucose';
    case Food = 'food';
    case Insulin = 'insulin';
    case Meds = 'meds';
    case Vitals = 'vitals';
    case Exercise = 'exercise';
    case Water = 'water';

    /** @return list<string> */
    public function sampleTypeValues(): array
    {
        if ($this === self::Water) {
            return [RecordWaterIntakeAction::WATER_TYPE];
        }

        $types = match ($this) {
            self::Glucose => [HealthSyncType::BloodGlucose],
            self::Food => [HealthSyncType::Carbohydrates, HealthSyncType::Protein, HealthSyncType::TotalFat, HealthSyncType::DietaryEnergy, HealthSyncType::Fiber],
            self::Insulin => [HealthSyncType::Insulin],
            self::Meds => [HealthSyncType::Medication, HealthSyncType::MedicationDoseEvent],
            self::Vitals => [HealthSyncType::Weight, HealthSyncType::BloodPressureSystolic, HealthSyncType::BloodPressureDiastolic, HealthSyncType::A1c],
            self::Exercise => [HealthSyncType::ExerciseMinutes, HealthSyncType::Workouts],
        };

        return array_map(fn (HealthSyncType $type): string => $type->value, $types);
    }
}
