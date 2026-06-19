<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Exceptions\AccountLinkException;
use App\Models\User;

/** @codeCoverageIgnore */
final readonly class LinkOrCreateUserForProvider
{
    /**
     * @param  'google_id'|'apple_id'  $providerColumn
     */
    public function handle(
        string $providerColumn,
        string $providerId,
        ?string $email,
        ?string $name,
        bool $emailTrustworthy,
    ): User {
        $user = User::query()->where($providerColumn, $providerId)->first();

        if ($user instanceof User) {
            return $user;
        }

        if ($email !== null) {
            $existing = User::query()->where('email', $email)->first();

            if ($existing instanceof User) {
                if ($emailTrustworthy && $existing->email_verified_at !== null) {
                    $existing->update([$providerColumn => $providerId]);

                    return $existing;
                }

                throw new AccountLinkException();
            }
        }

        return User::query()->create([
            $providerColumn => $providerId,
            'name' => $name ?? 'No Name',
            'email' => $email,
            'email_verified_at' => $emailTrustworthy ? now() : null,
        ]);
    }
}
