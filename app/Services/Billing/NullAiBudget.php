<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\ProvidesAiBudget;
use App\Data\Billing\AiBudget;
use App\Models\User;

final readonly class NullAiBudget implements ProvidesAiBudget
{
    public function forUser(User $user): ?AiBudget
    {
        return null;
    }
}
