<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsentPurpose;
use App\Enums\ConsentSource;
use App\Models\User;

final readonly class RecordSignUpAgreements
{
    public function __construct(private RecordConsent $recordConsent) {}

    public function handle(User $user): void
    {
        $user->update([
            'accepted_disclaimer_at' => now(),
            'terms_accepted_at' => now(),
            'privacy_accepted_at' => now(),
            'consent_version' => config()->string('plate.terms_version'),
            'privacy_version' => config()->string('plate.privacy_policy_version'),
        ]);

        $this->recordConsent->handle($user, ConsentPurpose::ProcessHealthData, true, ConsentSource::SignUp);
    }
}
