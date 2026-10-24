<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\TracksPhotoConversion;
use App\Models\User;

final readonly class NullPhotoConversion implements TracksPhotoConversion
{
    public function metadata(?User $user, ?string $guestId): array
    {
        return [];
    }

    public function track(?User $user, ?string $guestId, string $event, string $key): void {}
}
