<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\AiAllowance;
use App\Enums\AiAllowanceReset;
use App\Enums\SubscriptionTier;
use App\Models\AiUsage;
use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class ResolveAiAllowance
{
    public function __construct(private ResolvesUserTier $tiers) {}

    public function handle(User $user): ?AiAllowance
    {
        $entitlement = $this->tiers->resolve($user);

        if ($entitlement->isUnrestricted()) {
            return null;
        }

        /** @var array<string, array{usd: float|int, resets: string}> $allowances */
        $allowances = config()->array('plate.ai_allowance');
        $plan = $allowances[$entitlement->tier->value] ?? $allowances[SubscriptionTier::Free->value];
        $reset = AiAllowanceReset::from($plan['resets']);
        $periodStart = $reset->periodStart(CarbonImmutable::now($user->resolveTimezone()));

        $used = (float) AiUsage::query()
            ->forUser($user)
            ->where('created_at', '>=', $periodStart->utc())
            ->sum('cost');

        return new AiAllowance(
            tier: $entitlement->tier,
            resets: $reset,
            used: $used,
            limit: (float) $plan['usd'],
            resetsAt: $reset->nextReset($periodStart),
        );
    }
}
