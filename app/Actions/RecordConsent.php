<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsentPurpose;
use App\Enums\ConsentSource;
use App\Models\MealPhotoClassification;
use App\Models\MealPhotoSample;
use App\Models\User;

final readonly class RecordConsent
{
    public function handle(User $user, ConsentPurpose $purpose, bool $granted, ConsentSource $source): void
    {
        $alreadyRecorded = $granted
            ? $user->hasConsentedTo($purpose)
            : $user->consentDecision($purpose) === false;

        if ($alreadyRecorded) {
            return;
        }

        $user->consents()->create([
            'purpose' => $purpose,
            'version' => $purpose->version(),
            'granted' => $granted,
            'source' => $source,
        ]);

        if (! $granted && $purpose === ConsentPurpose::ImproveFoodRecognition) {
            MealPhotoClassification::deleteForUser($user->id);
            MealPhotoSample::deleteForUser($user);
        }
    }
}
