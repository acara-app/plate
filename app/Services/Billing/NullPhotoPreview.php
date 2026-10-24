<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\ManagesPhotoPreview;
use App\Models\User;

final readonly class NullPhotoPreview implements ManagesPhotoPreview
{
    public function activate(User $user, ?string $guestId): void
    {
        abort(404);
    }
}
