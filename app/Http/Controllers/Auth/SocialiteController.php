<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\FindOrCreateUserFromGoogleOAuth;
use App\Exceptions\AccountLinkException;
use App\Models\PlanPurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

final readonly class SocialiteController
{
    public function __construct(private FindOrCreateUserFromGoogleOAuth $findOrCreateUser)
    {
        //
    }

    public function redirect(Request $request): SymfonyRedirectResponse
    {
        $request->session()->forget('snap_to_track.google_oauth_purchase');

        return Socialite::driver('google')->redirect();
    }

    public function redirectForPurchase(Request $request): SymfonyRedirectResponse
    {
        $session = $request->session();
        $purchaseId = $session->get('snap_to_track.purchase');
        $purchase = is_string($purchaseId) ? PlanPurchase::query()->find($purchaseId) : null;

        if (! $purchase instanceof PlanPurchase || ! $purchase->isPaid() || $purchase->isClaimed() || $purchase->isCancelled()) {
            return to_route('snap-to-track.purchase');
        }

        $session->forget(['snap_to_track.google_claim', 'snap_to_track.google_oauth_purchase']);

        try {
            /** @var GoogleProvider $provider */
            $provider = Socialite::driver('google');
            $redirect = $provider->with(['prompt' => 'select_account'])->redirect();
            $session->put('snap_to_track.google_oauth_purchase', $purchase->id);

            return $redirect;
        } catch (Throwable $throwable) {
            report($throwable);
            $session->forget('state');

            return $this->purchaseError('Google sign-in could not start. Try again or use your email link.');
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        $purchaseId = $request->session()->pull('snap_to_track.google_oauth_purchase');

        if (is_string($purchaseId)) {
            return $this->purchaseCallback($request, $purchaseId);
        }

        if (Auth::check()) {
            return to_route('dashboard');
        }

        try {
            $googleUser = Socialite::driver('google')->user();

            $user = $this->findOrCreateUser->handle($googleUser);

            Auth::login($user, remember: true);

            if ($user->wasRecentlyCreated) {
                Inertia::flash('analytics', [
                    'name' => 'signup_completed',
                    'properties' => ['method' => 'google'],
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

    private function purchaseCallback(Request $request, string $purchaseId): RedirectResponse
    {
        $session = $request->session();
        $session->put('snap_to_track.purchase', $purchaseId);

        $purchase = PlanPurchase::query()->find($purchaseId);

        if (! $purchase instanceof PlanPurchase || $purchase->isCancelled() || $purchase->isClaimed()) {
            $session->forget('state');

            return to_route('snap-to-track.purchase');
        }

        if ($request->has('error')) {
            $session->forget('state');

            return $this->purchaseError('Google sign-in did not finish. Try again or use your email link.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $throwable) {
            report($throwable);
            $session->forget('state');

            return $this->purchaseError('Google sign-in did not finish. Try again or use your email link.');
        }

        $email = $googleUser->getEmail();
        $subject = (string) $googleUser->getId();
        $claims = $googleUser instanceof AbstractUser ? $googleUser->getRaw() : [];

        if (($claims['email_verified'] ?? false) !== true || ! is_string($email) || $email === '' || $subject === '') {
            return $this->purchaseError('Google could not verify your email address. Use your email link to activate Starter.');
        }

        $domain = Str::lower(Str::afterLast($email, '@'));
        $hostedDomain = $claims['hd'] ?? null;

        if ($domain !== 'gmail.com' && (! is_string($hostedDomain) || $hostedDomain === '' || Str::lower($hostedDomain) !== $domain)) {
            return $this->purchaseError('Google does not manage this email domain. Use your email link to activate Starter.');
        }

        $session->put('snap_to_track.google_claim', [
            'purchase' => $purchase->id,
            'email' => $email,
            'google_id' => $subject,
            'name' => $googleUser->getName(),
        ]);

        return to_route('snap-to-track.purchase');
    }

    private function purchaseError(string $message): RedirectResponse
    {
        return to_route('snap-to-track.purchase')->with('snap_to_track.google_error', $message);
    }
}
