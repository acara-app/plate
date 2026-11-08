<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class AcknowledgePrivacyPolicy
{
    public function handle(User $user): void
    {
        $user->update([
            'privacy_version' => config()->string('plate.privacy_policy_version'),
            'privacy_accepted_at' => now(),
        ]);
    }
}
