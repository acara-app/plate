<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MeasurementAssessment;
use App\Models\UserProfile;
use App\Services\BodyMassIndex;

final readonly class AssessBodyMeasurements
{
    private const float CONFIRMED_WEIGHT_TOLERANCE = 0.1;

    private const float CONFIRMED_HEIGHT_TOLERANCE_CM = 3.0;

    public function handle(float $weightKg, float $heightCm, ?UserProfile $savedProfile = null): MeasurementAssessment
    {
        if (BodyMassIndex::isPlausible($weightKg, $heightCm) || $this->staysCloseToSavedMeasurements($weightKg, $heightCm, $savedProfile)) {
            return MeasurementAssessment::Plausible;
        }

        if (BodyMassIndex::looksLikePounds($weightKg, $heightCm)) {
            return MeasurementAssessment::LooksLikePounds;
        }

        if (BodyMassIndex::looksLikeInches($weightKg, $heightCm)) {
            return MeasurementAssessment::LooksLikeInches;
        }

        return MeasurementAssessment::Implausible;
    }

    private function staysCloseToSavedMeasurements(float $weightKg, float $heightCm, ?UserProfile $savedProfile): bool
    {
        if (! $savedProfile instanceof UserProfile || $savedProfile->needs_re_onboarding || $savedProfile->weight === null || $savedProfile->height === null) {
            return false;
        }

        return abs($weightKg - $savedProfile->weight) <= $savedProfile->weight * self::CONFIRMED_WEIGHT_TOLERANCE
            && abs($heightCm - $savedProfile->height) <= self::CONFIRMED_HEIGHT_TOLERANCE_CM;
    }
}
