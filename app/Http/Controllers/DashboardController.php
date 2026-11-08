<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function show(#[CurrentUser] User $user): Response
    {
        return Inertia::render('dashboard', [
            'showPrivacyUpdate' => $user->needsPrivacyPolicyNotice(),
        ]);
    }
}
