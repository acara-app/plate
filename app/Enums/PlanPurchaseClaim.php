<?php

declare(strict_types=1);

namespace App\Enums;

enum PlanPurchaseClaim: string
{
    case Claimed = 'claimed';
    case ClaimedElsewhere = 'claimed_elsewhere';
    case ActivePlan = 'active_plan';
    case OtherCustomer = 'other_customer';
    case Cancelled = 'cancelled';

    public function needsSupport(): bool
    {
        return in_array($this, [self::ActivePlan, self::OtherCustomer], true);
    }
}
