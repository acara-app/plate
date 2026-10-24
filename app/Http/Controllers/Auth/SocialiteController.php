<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\FindOrCreateUserFromGoogleOAuth;
use App\Contracts\Billing\TracksPhotoConversion;
use App\Data\Billing\PhotoAnalysisContext;
use App\Exceptions\AccountLinkException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
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

            if ($user->wasRecentlyCreated) {
                $tracking = resolve(TracksPhotoConversion::class);
                $tracking->track($user, PhotoAnalysisContext::guestId(request()), 'signup', 'signup');
                Inertia::flash('analytics', [
                    'name' => 'signup_completed',
                    'properties' => [
                        'method' => 'google',
                        ...$tracking->metadata($user, PhotoAnalysisContext::guestId(request())),
                    ],
                ]);
            }

            return redirect()->intended(route('dashboard', absolute: false));

        } catch (AccountLinkException) {
            return to_route('login')->with('error', __('This email is already registered. Please sign in with your password.'));
        } catch (Throwable $throwable) {
            report($throwable);

            return to_route('login')->with('error', 'Something went wrong!');
        }
    }
}
