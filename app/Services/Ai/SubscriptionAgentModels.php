<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\Ai\ResolvesAgentModel;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Ai\AgentModelData;
use App\Data\Billing\TierEntitlement;
use App\Models\User;
use InvalidArgumentException;

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

        if ($entitlement->isPaying() && is_array($profile) && isset($profile['paid'])) {
            try {
                return $this->models->forAgent($agentClass, 'paid');
            } catch (InvalidArgumentException $invalidArgumentException) {
                report($invalidArgumentException);
            }
        }

        return $this->models->forAgent($agentClass);
    }
}
