<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HealthEntry;
use App\Models\User;

final class HealthEntryPolicy
{
    public function update(User $user, HealthEntry $healthEntry): bool
    {
        return $user->id === $healthEntry->user_id;
    }

    public function delete(User $user, HealthEntry $healthEntry): bool
    {
        return $user->id === $healthEntry->user_id;
    }
}
