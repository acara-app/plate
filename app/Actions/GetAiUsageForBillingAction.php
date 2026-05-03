<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\Billing\ResolvesUserTier;
use App\Enums\SubscriptionTier;
use App\Models\AiUsage;
use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class GetAiUsageForBillingAction
{
    public function __construct(
        private ResolvesUserTier $resolveUserTier,
    ) {}

    /**
     * @return array{
     *     tier: string,
     *     tier_label: string,
     *     payment_pending: bool,
     *     premium_enforcement_active: bool,
     *     rolling: array{current: int, limit: int, percentage: int, resets_in: string, over_limit: bool},
     *     weekly: array{current: int, limit: int, percentage: int, resets_in: string, over_limit: bool},
     *     monthly: array{current: int, limit: int, percentage: int, resets_in: string, over_limit: bool}
     * }
     */
    public function handle(User $user): array
    {
        $entitlement = $this->resolveUserTier->resolve($user);
        $limits = $this->limitsForTier($entitlement->tier);
        $multiplier = config()->integer('plate.credit_multiplier', 1);
        $now = CarbonImmutable::now();
        $rollingHours = (int) $limits['rolling']['period_hours'];
        $rollingPeriodStart = $now->subHours($rollingHours);
        $subscription = $user->activeSubscription();
        $periodStart = $this->getPeriodStart($subscription);
        $periodEnd = $this->getPeriodEnd($subscription);

        $rollingCost = $this->getCostForPeriod($user, $rollingPeriodStart, $now);
        $periodCost = $this->getCostForPeriod($user, $periodStart, $now);
        $rollingResetsAt = $this->rollingResetsAt($user, $now, $rollingPeriodStart, $rollingHours);

        return [
            'tier' => $entitlement->tier->value,
            'tier_label' => $entitlement->tier->label(),
            'payment_pending' => $entitlement->isPaymentPending(),
            'premium_enforcement_active' => $entitlement->premiumEnforcementActive,
            'rolling' => $this->buildBucket(
                $rollingCost,
                (float) $limits['rolling']['limit'],
                $multiplier,
                $rollingResetsAt,
            ),
            'weekly' => $this->buildBucket(
                $periodCost,
                (float) $limits['weekly']['limit'],
                $multiplier,
                $periodEnd,
            ),
            'monthly' => $this->buildBucket(
                $periodCost,
                (float) $limits['monthly']['limit'],
                $multiplier,
                $periodEnd,
            ),
        ];
    }

    /**
     * @return array{current: int, limit: int, percentage: int, resets_in: string, over_limit: bool}
     */
    private function buildBucket(float $cost, float $limit, int $multiplier, CarbonImmutable $resetTime): array
    {
        return [
            'current' => $this->toCredits($cost, $multiplier),
            'limit' => $this->toCredits($limit, $multiplier),
            'percentage' => $this->calculatePercentage($cost, $limit),
            'resets_in' => $this->formatResetsIn($resetTime),
            'over_limit' => $limit > 0 && $cost > $limit,
        ];
    }

    /**
     * @return array{rolling: array{limit: float, period_hours: int}, weekly: array{limit: float, period_days: int}, monthly: array{limit: float, period_days: int}}
     */
    private function limitsForTier(SubscriptionTier $tier): array
    {
        /** @var array<string, array{rolling: array{limit: float, period_hours: int}, weekly: array{limit: float, period_days: int}, monthly: array{limit: float, period_days: int}}> $tierLimits */
        $tierLimits = config('plate.tier_limits', []);

        return $tierLimits[$tier->value] ?? $tierLimits[SubscriptionTier::Free->value];
    }

    // @codeCoverageIgnoreStart
    private function getPeriodStart(?object $subscription): CarbonImmutable
    {
        if (! $subscription || ! isset($subscription->current_period_start)) {
            return CarbonImmutable::now()->startOfWeek();
        }

        /** @var float|int|string $timestamp */
        $timestamp = $subscription->current_period_start;

        return CarbonImmutable::createFromTimestamp($timestamp);
    }

    private function getPeriodEnd(?object $subscription): CarbonImmutable
    {
        if (! $subscription || ! isset($subscription->current_period_end)) {
            return CarbonImmutable::now()->endOfWeek();
        }

        /** @var float|int|string $timestamp */
        $timestamp = $subscription->current_period_end;

        return CarbonImmutable::createFromTimestamp($timestamp);
    }

    // @codeCoverageIgnoreEnd

    private function getCostForPeriod(User $user, CarbonImmutable $start, CarbonImmutable $end): float
    {
        return (float) AiUsage::query()
            ->forUser($user)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->sum('cost');
    }

    private function rollingResetsAt(User $user, CarbonImmutable $now, CarbonImmutable $periodStart, int $hours): CarbonImmutable
    {
        $oldest = AiUsage::query()
            ->forUser($user)
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<=', $now)
            ->min('created_at');

        return is_string($oldest)
            ? CarbonImmutable::parse($oldest)->addHours($hours)
            : $now->addHours($hours);
    }

    private function toCredits(float $dollars, int $multiplier): int
    {
        return (int) round($dollars * $multiplier);
    }

    private function calculatePercentage(float $current, float $limit): int
    {
        // @codeCoverageIgnoreStart
        if ($limit <= 0) {
            return 0;
        }

        // @codeCoverageIgnoreEnd

        return (int) min(100, round(($current / $limit) * 100));
    }

    private function formatResetsIn(CarbonImmutable $resetTime): string
    {
        $now = CarbonImmutable::now();
        $diff = $now->diff($resetTime);

        if ($diff->d > 0) {
            return $diff->d.' days '.$diff->h.' hours';
        }

        // @codeCoverageIgnoreStart
        if ($diff->h > 0) {
            return $diff->h.' hours '.$diff->i.' minutes';
        }

        return $diff->i.' minutes';
        // @codeCoverageIgnoreEnd
    }
}
