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
    case CheckIn = 'check_in';

    /** @return list<string> */
    public function sampleTypeValues(): array
    {
        return match ($this) {
            self::Glucose => [HealthSyncType::BloodGlucose->value],
            self::Food => [HealthSyncType::Carbohydrates->value, HealthSyncType::Protein->value, HealthSyncType::TotalFat->value, HealthSyncType::DietaryEnergy->value, HealthSyncType::Fiber->value],
            self::Insulin => [HealthSyncType::Insulin->value],
            self::Meds => [HealthSyncType::Medication->value, HealthSyncType::MedicationDoseEvent->value],
            self::Vitals => [HealthSyncType::Weight->value, HealthSyncType::BloodPressureSystolic->value, HealthSyncType::BloodPressureDiastolic->value, HealthSyncType::A1c->value],
            self::Exercise => [HealthSyncType::ExerciseMinutes->value, HealthSyncType::Workouts->value],
            self::Water => [RecordWaterIntakeAction::WATER_TYPE],
            self::CheckIn => DailyCheckInMetric::values(),
        };
    }
}
