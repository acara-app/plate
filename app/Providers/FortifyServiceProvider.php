<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

final class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bootFortifyDefaults();
        $this->bootRateLimitingDefaults();
    }

    private function bootFortifyDefaults(): void
    {
        Fortify::twoFactorChallengeView(fn () => Inertia::render('user-two-factor-authentication-challenge/show'));
        Fortify::confirmPasswordView(function (Request $request): Response {
            $user = $request->user();

            return Inertia::render('user-password-confirmation/create', [
                'hasPassword' => $user instanceof User && filled($user->password),
                'canConfirmWithPasskey' => Features::canManagePasskeys()
                    && $user instanceof User && $user->hasPasskeysEnabled(),
            ]);
        });
    }

    private function bootRateLimitingDefaults(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->string('email')->value().$request->ip()));
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));
        RateLimiter::for('passkeys', function (Request $request): Limit {
            $credentialId = $request->input('credential.id');
            $key = is_string($credentialId) && $credentialId !== ''
                ? $credentialId
                : $request->session()->getId();

            return Limit::perMinute(10)->by($key.'|'.$request->ip());
        });
    }
}
