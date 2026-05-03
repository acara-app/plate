<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use App\Data\Billing\TierEntitlement;
use App\Models\User;

interface ResolvesUserTier
{
    public function resolve(User $user): TierEntitlement;
}
