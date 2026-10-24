<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\UserPasskeyData;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

final readonly class UserPasskeyController
{
    public function edit(#[CurrentUser] User $user): Response
    {
        abort_unless(Features::canManagePasskeys(), 404);

        return Inertia::render('user-passkeys/edit', [
            'passkeys' => UserPasskeyData::collect($user->passkeys()->latest()->get()),
        ]);
    }
}
