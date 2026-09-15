<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use App\Data\Billing\AiBudget;
use App\Models\User;

interface ProvidesAiBudget
{
    public function forUser(User $user): ?AiBudget;
}
