<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Auth\ResolveGoogleUser;
use App\Models\User;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final readonly class FindOrCreateUserFromGoogleOAuth
{
    public function __construct(private ResolveGoogleUser $resolveGoogleUser) {}

    public function handle(SocialiteUser $googleUser): User
    {
        return $this->resolveGoogleUser->handle([
            'sub' => (string) $googleUser->getId(),
            'email' => $googleUser->getEmail(),
            'email_verified' => $googleUser instanceof AbstractUser && ($googleUser->getRaw()['email_verified'] ?? false) === true,
            'name' => $googleUser->getName(),
        ]);
    }
}
