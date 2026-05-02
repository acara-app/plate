<?php

declare(strict_types=1);

namespace App\Enums\Telemetry;

enum PaywallEvent: string
{
    case CreditWarningShown = 'credit_warning_shown';
    case UsageLimitExceeded = 'usage_limit_exceeded';
    case PaywallShown = 'paywall_shown';
    case PaywallDismissed = 'paywall_dismissed';
    case UpgradeClicked = 'upgrade_clicked';
    case GatedFeatureAttempt = 'gated_feature_attempt';
    case CheckoutCompleted = 'checkout_completed';
    case SubscriptionCanceled = 'subscription_canceled';
}
