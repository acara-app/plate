<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use App\Models\User;

interface TracksPhotoConversion
{
    /** @return array<string, string> */
    public function metadata(?User $user, ?string $guestId): array;

    public function track(?User $user, ?string $guestId, string $event, string $key): void;
}
