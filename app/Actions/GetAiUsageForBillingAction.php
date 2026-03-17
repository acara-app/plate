<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AiUsage;
use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class GetAiUsageForBillingAction
{
    /**
     * @return array{rolling: array{current: int, limit: int, percentage: int, resets_in: string}, weekly: array{current: int, limit: int, percentage: int, resets_in: string}, monthly: array{current: int, limit: int, percentage: int, resets_in: string}}
     */
    public function handle(User $user): array
    {
        /** @var array{rolling: array{period_hours: int, limit: float}, weekly: array{limit: float}, monthly: array{limit: float}} $limits */
        $limits = config('plate.ai_usage_limits');

        $multiplier = (int) config('plate.credit_multiplier'); /** @phpstan-ignore cast.int */
        $rollingPeriodStart = now()->subHours($limits['rolling']['period_hours']);
        $rollingLimit = (float) $limits['rolling']['limit'];

        $subscription = $user->activeSubscription();
        $periodStart = $this->getPeriodStart($subscription);
        $periodEnd = $this->getPeriodEnd($subscription);

        $weeklyLimit = (float) $limits['weekly']['limit'];
        $monthlyLimit = (float) $limits['monthly']['limit'];

        $rollingCost = $this->getCostForPeriod($user, $rollingPeriodStart, now());
        $weeklyCost = $this->getCostForPeriod($user, $periodStart, now());
        $monthlyCost = $this->getCostForPeriod($user, $periodStart, now());

        return [
            'rolling' => [
                'current' => $this->toCredits($rollingCost, $multiplier),
                'limit' => $this->toCredits($rollingLimit, $multiplier),
                'percentage' => $this->calculatePercentage($rollingCost, $rollingLimit),
                'resets_in' => $this->formatResetsIn(now()->addHours($limits['rolling']['period_hours'])),
            ],
            'weekly' => [
                'current' => $this->toCredits($weeklyCost, $multiplier),
                'limit' => $this->toCredits($weeklyLimit, $multiplier),
                'percentage' => $this->calculatePercentage($weeklyCost, $weeklyLimit),
                'resets_in' => $this->formatResetsIn($periodEnd),
            ],
            'monthly' => [
                'current' => $this->toCredits($monthlyCost, $multiplier),
                'limit' => $this->toCredits($monthlyLimit, $multiplier),
                'percentage' => $this->calculatePercentage($monthlyCost, $monthlyLimit),
                'resets_in' => $this->formatResetsIn($periodEnd),
            ],
        ];
    }

    // @codeCoverageIgnoreStart
    private function getPeriodStart(?object $subscription): CarbonImmutable
    {
        if ($subscription && isset($subscription->current_period_start)) {
            /** @var float|int|string $timestamp */
            $timestamp = $subscription->current_period_start;

            return CarbonImmutable::createFromTimestamp($timestamp);
        }

        return CarbonImmutable::now()->startOfWeek();
    }

    private function getPeriodEnd(?object $subscription): CarbonImmutable
    {
        if ($subscription && isset($subscription->current_period_end)) {
            /** @var float|int|string $timestamp */
            $timestamp = $subscription->current_period_end;

            return CarbonImmutable::createFromTimestamp($timestamp);
        }

        return CarbonImmutable::now()->endOfWeek();
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
