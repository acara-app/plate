<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AcceptHealthDataConsent;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class AcceptHealthDataConsentController
{
    public function __construct(private AcceptHealthDataConsent $acceptHealthDataConsent) {}

    public function __invoke(#[CurrentUser] User $user): RedirectResponse
    {
        $this->acceptHealthDataConsent->handle($user);

        return back();
    }
}
