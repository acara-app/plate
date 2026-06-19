<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

/** @codeCoverageIgnore */
final readonly class IssueMobileAuthToken
{
    /**
     * @param  list<string>  $abilities
     */
    public function handle(User $user, string $deviceIdentifier, array $abilities): NewAccessToken
    {
        return $user->createToken('mobile:'.$deviceIdentifier, $abilities);
    }
}
