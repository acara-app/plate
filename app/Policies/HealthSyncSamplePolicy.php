<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HealthSyncSample;
use App\Models\User;

final class HealthSyncSamplePolicy
{
    public function update(User $user, HealthSyncSample $healthSyncSample): bool
    {
        return $user->id === $healthSyncSample->user_id;
    }

    public function delete(User $user, HealthSyncSample $healthSyncSample): bool
    {
        return $user->id === $healthSyncSample->user_id;
    }
}
