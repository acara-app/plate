<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

/** @codeCoverageIgnore */
final readonly class ResolveGoogleUser
{
    public function __construct(private LinkOrCreateUserForProvider $linkOrCreateUser) {}

    /**
     * @param  array{sub: string, email: string|null, email_verified: bool, name: string|null}  $claims
     */
    public function handle(array $claims): User
    {
        return $this->linkOrCreateUser->handle(
            'google_id',
            $claims['sub'],
            $claims['email'],
            $claims['name'],
            $claims['email_verified'],
        );
    }
}
