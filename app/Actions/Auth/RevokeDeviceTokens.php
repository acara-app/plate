<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

final readonly class RevokeDeviceTokens
{
    public function handle(User $user, ?string $deviceIdentifier, ?int $legacyDeviceId = null): void
    {
        $names = [];

        if ($deviceIdentifier !== null && $deviceIdentifier !== '') {
            $names[] = 'mobile:'.$deviceIdentifier;
        }

        if ($legacyDeviceId !== null) {
            $names[] = 'mobile-sync:'.$legacyDeviceId;
        }

        if ($names === []) {
            return;
        }

        $user->tokens()->whereIn('name', $names)->delete();
    }
}
