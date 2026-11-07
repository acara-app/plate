<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\Ai\ResolvesAgentModel;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Ai\AgentModelData;
use App\Data\Billing\TierEntitlement;
use App\Enums\SubscriptionTier;
use App\Models\User;

final class SubscriptionAgentModels implements ResolvesAgentModel
{
    /** @var array<int, TierEntitlement> */
    private array $entitlements = [];

    public function __construct(
        private readonly ConfiguredAgentModels $models,
        private readonly ResolvesUserTier $tiers,
    ) {}

    public function resolve(string $agentClass, ?User $user = null): AgentModelData
    {
        if (! $user instanceof User) {
            return $this->models->forAgent($agentClass);
        }

        $entitlement = $this->entitlements[$user->id] ??= $this->tiers->resolve($user);
        $profile = config()->array('ai.agent_profiles')[$agentClass] ?? null;
        $paid = ! $entitlement->isUnrestricted() && ! $entitlement->isPaymentPending()
            && $entitlement->tier !== SubscriptionTier::Free
            && is_array($profile) && isset($profile['paid']);

        return $this->models->forAgent($agentClass, $paid ? 'paid' : 'standard');
    }
}
