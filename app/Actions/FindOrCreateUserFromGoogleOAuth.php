<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final readonly class FindOrCreateUserFromGoogleOAuth
{
    /**
     * Find or create a user from Google OAuth data.
     */
    public function handle(SocialiteUser $googleUser): User
    {
        // Try to find existing user by Google ID
        $user = User::query()->where('google_id', $googleUser->getId())->first();

        if ($user instanceof User) {
            // Update existing user's information
            $user->update([
                'google_id' => $googleUser->getId(),
                'name' => $googleUser->getName() ?? $user->name,
                'email' => $googleUser->getEmail(),
            ]);

            return $user;
        }

        // Check if user exists with this email
        $user = User::query()->where('email', $googleUser->getEmail())->first();

        if ($user instanceof User) {
            // Link Google account to existing user
            $user->update([
                'google_id' => $googleUser->getId(),
                'name' => $googleUser->getName() ?? $user->name,
            ]);

            return $user;
        }

        // Create new user
        return User::query()->create([
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName() ?? 'No Name',
            'email' => $googleUser->getEmail(),
            'email_verified_at' => now(),
        ]);
    }
}
