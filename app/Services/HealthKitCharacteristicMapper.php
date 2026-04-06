<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BloodType;
use App\Enums\HealthSyncType;
use App\Enums\Sex;
use Illuminate\Support\Facades\Date;

final class HealthKitCharacteristicMapper
{
    /**
     * @return array<string, mixed>
     */
    public function map(HealthSyncType $syncType, float|int|string $value): array
    {
        return match ($syncType) {
            HealthSyncType::BiologicalSex => ['sex' => $this->mapBiologicalSex((int) $value)],
            HealthSyncType::DateOfBirth => $this->mapDateOfBirth((float) $value),
            HealthSyncType::BloodType => ['blood_type' => $this->mapBloodType((int) $value)],
            default => [],
        };
    }

    private function mapBiologicalSex(int $healthKitValue): ?Sex
    {
        return match ($healthKitValue) {
            1 => Sex::Female,
            2 => Sex::Male,
            3 => Sex::Other,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDateOfBirth(float $healthKitValue): array
    {
        $dateString = (string) (int) $healthKitValue;

        if (mb_strlen($dateString) !== 8) {
            return [];
        }

        $date = Date::createFromFormat('Ymd', $dateString);

        if (! $date) {
            return []; // @codeCoverageIgnore
        }

        return [
            'date_of_birth' => $date->startOfDay(),
            'age' => $date->age,
        ];
    }

    private function mapBloodType(int $healthKitValue): ?BloodType
    {
        return match ($healthKitValue) {
            1 => BloodType::APositive,
            2 => BloodType::ANegative,
            3 => BloodType::BPositive,
            4 => BloodType::BNegative,
            5 => BloodType::ABPositive,
            6 => BloodType::ABNegative,
            7 => BloodType::OPositive,
            8 => BloodType::ONegative,
            default => null,
        };
    }
}
