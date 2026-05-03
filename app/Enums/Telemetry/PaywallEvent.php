<?php

declare(strict_types=1);

namespace App\Enums\Telemetry;

enum PaywallEvent: string
{
    case CreditWarningShown = 'credit_warning_shown';
    case UsageLimitExceeded = 'usage_limit_exceeded';
    case CheckoutCompleted = 'checkout_completed';
    case SubscriptionCanceled = 'subscription_canceled';
}
