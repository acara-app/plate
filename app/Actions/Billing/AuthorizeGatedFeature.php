<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Contracts\Billing\ResolvesUserTier;
use App\Contracts\Telemetry\EmitsPaywallEvents;
use App\Enums\GatedFeature;
use App\Enums\Telemetry\PaywallEvent;
use App\Exceptions\Billing\FeatureGateException;
use App\Models\User;

final readonly class AuthorizeGatedFeature
{
    public function __construct(
        private ResolvesUserTier $resolver,
        private EmitsPaywallEvents $telemetry,
    ) {}

    public function handle(User $user, GatedFeature $feature): void
    {
        $entitlement = $this->resolver->resolve($user);

        if ($entitlement->isUnrestricted()) {
            return;
        }

        if ($entitlement->isAtLeast($feature->requiredTier())) {
            return;
        }

        $this->telemetry->emit(
            event: PaywallEvent::GatedFeatureAttempt,
            user: $user,
            payload: [
                'tier_current' => $entitlement->tier->value,
                'tier_required' => $feature->requiredTier()->value,
                'feature' => $feature->value,
            ],
        );

        throw new FeatureGateException(
            feature: $feature,
            currentTier: $entitlement->tier,
            requiredTier: $feature->requiredTier(),
        );
    }

    public function check(User $user, GatedFeature $feature): bool
    {
        $entitlement = $this->resolver->resolve($user);

        if ($entitlement->isUnrestricted()) {
            return true;
        }

        return $entitlement->isAtLeast($feature->requiredTier());
    }
}
