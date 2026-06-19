<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

/** @codeCoverageIgnore */
final readonly class FindOrCreateUserFromAppleSignIn
{
    public function __construct(private LinkOrCreateUserForProvider $linkOrCreateUser) {}

    /**
     * @param  array{sub: string, email: string|null, email_verified: bool, is_private_relay: bool}  $claims
     */
    public function handle(array $claims, ?string $name): User
    {
        return $this->linkOrCreateUser->handle(
            'apple_id',
            $claims['sub'],
            $claims['email'],
            $name,
            $claims['email_verified'] && ! $claims['is_private_relay'],
        );
    }
}
