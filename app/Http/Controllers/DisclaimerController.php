<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DisclaimerController
{
    public function show(): Response
    {
        return Inertia::render('disclaimer/show');
    }

    public function accept(Request $request, #[CurrentUser] User $user): RedirectResponse
    {
        $request->validate([
            'accepted' => ['required', 'accepted'],
        ]);

        $user->update(['accepted_disclaimer_at' => now()]);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
