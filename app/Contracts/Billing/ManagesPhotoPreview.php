<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use App\Models\User;

interface ManagesPhotoPreview
{
    public function activate(User $user, ?string $guestId): void;
}
