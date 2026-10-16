<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

final readonly class UserEmailVerification
{
    public function update(EmailVerificationRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        if (! $user->hasVerifiedEmail()) {
            $request->fulfill();
        }

        $fallback = $request->session()->has('checkout.selected_product')
            ? route('checkout.subscription', absolute: false)
            : route('dashboard', absolute: false).'?verified=1';

        return redirect()->intended($fallback);
    }
}
