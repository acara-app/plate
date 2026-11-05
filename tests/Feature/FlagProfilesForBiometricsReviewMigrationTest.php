<?php

declare(strict_types=1);

use App\Models\UserProfile;
use App\Services\BodyMassIndex;

function runFlagProfilesForBiometricsReviewMigration(): void
{
    (require database_path('migrations/2026_11_05_004713_flag_profiles_for_biometrics_review.php'))->up();
}

it('flags existing profiles whose measurements fall outside the plausible range', function (?float $weight, ?float $height, bool $isFlagged): void {
    $lastUpdatedAt = now()->subDay()->toDateTimeString();
    $profile = UserProfile::factory()->create([
        'weight' => $weight,
        'height' => $height,
        'needs_re_onboarding' => false,
        'updated_at' => $lastUpdatedAt,
    ]);

    runFlagProfilesForBiometricsReviewMigration();

    $profile->refresh();

    expect($profile->needs_re_onboarding)->toBe($isFlagged)
        ->and($profile->updated_at->toDateTimeString())->toBe($lastUpdatedAt);
})->with([
    'pounds typed as kilograms' => [193.0, 175.0, true],
    'typical adult' => [70.0, 175.0, false],
    'just under the upper limit' => [137.81, 175.0, false],
    'at the upper limit' => [137.82, 175.0, true],
    'at the lower limit' => [45.94, 175.0, false],
    'just under the lower limit' => [45.93, 175.0, true],
    'height typed in inches' => [80.0, 70.0, true],
    'missing height' => [70.0, null, false],
    'missing weight' => [null, 175.0, false],
]);

it('flags exactly the profiles the onboarding plausibility check would question', function (float $weight, float $height): void {
    $profile = UserProfile::factory()->create([
        'weight' => $weight,
        'height' => $height,
        'needs_re_onboarding' => false,
    ]);

    runFlagProfilesForBiometricsReviewMigration();

    expect($profile->refresh()->needs_re_onboarding)->toBe(! BodyMassIndex::isPlausible($weight, $height));
})->with([
    [193.0, 175.0],
    [87.54, 175.0],
    [137.81, 175.0],
    [137.82, 175.0],
    [45.94, 175.0],
    [45.93, 175.0],
    [120.0, 150.0],
    [50.0, 200.0],
    [80.0, 70.0],
]);
