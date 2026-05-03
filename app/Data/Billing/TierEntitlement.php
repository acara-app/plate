<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Enums\SubscriptionTier;
use Carbon\CarbonInterface;

final readonly class TierEntitlement
{
    public function __construct(
        public SubscriptionTier $tier,
        public bool $premiumEnforcementActive = true,
        public bool $paymentPending = false,
        public ?CarbonInterface $gracePeriodEndsAt = null,
    ) {}

    public static function unrestricted(): self
    {
        return new self(
            tier: SubscriptionTier::Free,
            premiumEnforcementActive: false,
        );
    }

    public static function free(): self
    {
        return new self(tier: SubscriptionTier::Free);
    }

    public static function paymentPending(): self
    {
        return new self(
            tier: SubscriptionTier::Free,
            paymentPending: true,
        );
    }

    public function isUnrestricted(): bool
    {
        return ! $this->premiumEnforcementActive;
    }

    public function isPaymentPending(): bool
    {
        return $this->paymentPending;
    }

    public function inGracePeriod(): bool
    {
        return $this->gracePeriodEndsAt instanceof CarbonInterface;
    }
}
