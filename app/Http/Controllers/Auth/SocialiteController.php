<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\FindOrCreateUserFromGoogleOAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

final readonly class SocialiteController
{
    public function __construct(private FindOrCreateUserFromGoogleOAuth $findOrCreateUser)
    {
        //
    }

    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = $this->findOrCreateUser->handle($googleUser);

            Auth::login($user);

            return to_route('dashboard');

        } catch (Throwable) {

            return to_route('login')->with('error', 'Something went wrong!');
        }
    }
}
