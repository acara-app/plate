<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUser;
use App\Actions\DeleteUser;
use App\Contracts\Billing\TracksPhotoConversion;
use App\Data\Billing\PhotoAnalysisContext;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\DeleteUserRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserController
{
    public function create(): Response
    {
        return Inertia::render('user/create', [
            'snapIntent' => session('snap_to_track.intent'),
        ]);
    }

    public function store(CreateUserRequest $request, CreateUser $action): RedirectResponse
    {
        /** @var array<string, mixed> $attributes */
        $attributes = [
            ...$request->safe()->only(['name', 'email']),
            'accepted_disclaimer_at' => now(),
        ];

        $user = $action->handle(
            $attributes,
            $request->string('password')->value(),
        );

        Auth::login($user);

        $request->session()->regenerate();

        $tracking = resolve(TracksPhotoConversion::class);
        $tracking->track($user, PhotoAnalysisContext::guestId($request), 'signup', 'signup');

        Inertia::flash('analytics', [
            'name' => 'signup_completed',
            'properties' => [
                'method' => 'email',
                ...$tracking->metadata($user, PhotoAnalysisContext::guestId($request)),
            ],
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(DeleteUserRequest $request, #[CurrentUser] User $user, DeleteUser $action): RedirectResponse
    {
        Auth::logout();

        $action->handle($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
