<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final readonly class FindOrCreateUserFromGoogleOAuth
{
    public function handle(SocialiteUser $googleUser): User
    {
        $user = User::query()->where('google_id', $googleUser->getId())->first();

        if ($user instanceof User) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'name' => $googleUser->getName() ?? $user->name,
                'email' => $googleUser->getEmail(),
            ]);

            return $user;
        }

        $user = User::query()->where('email', $googleUser->getEmail())->first();

        if ($user instanceof User) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'name' => $googleUser->getName() ?? $user->name,
            ]);

            return $user;
        }

        return User::query()->create([
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName() ?? 'No Name',
            'email' => $googleUser->getEmail(),
            'email_verified_at' => now(),
        ]);
    }
}
