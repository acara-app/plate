<?php

declare(strict_types=1);

use App\Actions\AssessBodyMeasurements;
use App\Enums\MeasurementAssessment;
use App\Models\UserProfile;

covers(AssessBodyMeasurements::class);

it('assesses a weight and height combination', function (float $weightKg, float $heightCm, MeasurementAssessment $assessment): void {
    expect(resolve(AssessBodyMeasurements::class)->handle($weightKg, $heightCm))->toBe($assessment);
})->with([
    'typical adult' => [70.0, 175.0, MeasurementAssessment::Plausible],
    'pounds typed as kilograms' => [193.0, 175.0, MeasurementAssessment::LooksLikePounds],
    'inches typed as centimeters' => [80.0, 70.0, MeasurementAssessment::LooksLikeInches],
    'unexplained' => [30.0, 175.0, MeasurementAssessment::Implausible],
]);

it('accepts small changes to measurements that were already saved and confirmed', function (float $weightKg, float $heightCm, bool $needsReview, MeasurementAssessment $assessment): void {
    $savedProfile = UserProfile::factory()->create(['weight' => 135, 'height' => 170, 'needs_re_onboarding' => $needsReview]);

    expect(resolve(AssessBodyMeasurements::class)->handle($weightKg, $heightCm, $savedProfile))->toBe($assessment);
})->with([
    'unchanged' => [135.0, 170.0, false, MeasurementAssessment::Plausible],
    'a little lighter' => [130.0, 170.0, false, MeasurementAssessment::Plausible],
    'a big jump' => [160.0, 170.0, false, MeasurementAssessment::LooksLikePounds],
    'a different height' => [135.0, 160.0, false, MeasurementAssessment::LooksLikePounds],
    'still waiting for review' => [135.0, 170.0, true, MeasurementAssessment::LooksLikePounds],
]);
