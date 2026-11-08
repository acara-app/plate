<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsentPurpose;
use App\Enums\ConsentSource;
use App\Models\User;

final readonly class RecordConsent
{
    public function handle(User $user, ConsentPurpose $purpose, bool $granted, ConsentSource $source): void
    {
        if ($user->consentDecision($purpose) === $granted) {
            return;
        }

        $user->consents()->create([
            'purpose' => $purpose,
            'version' => $purpose->version(),
            'granted' => $granted,
            'source' => $source,
        ]);
    }
}
