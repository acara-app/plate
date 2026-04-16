<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use App\Models\User;
use App\Services\Null\NullPremiumGate;
use Illuminate\Container\Attributes\Bind;

#[Bind(NullPremiumGate::class)]
interface GatesPremiumFeatures
{
    public function isPremium(User $user, ?bool $storedIsVerified = null): bool;
}
