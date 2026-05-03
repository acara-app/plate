<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\CreditWarning;
use App\Enums\SubscriptionTier;
use App\Models\AiUsage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final readonly class BuildCreditWarning
{
    private const float WARNING_THRESHOLD = 0.80;

    public function currentState(User $user): ?CreditWarning
    {
        $entitlement = resolve(ResolvesUserTier::class)->resolve($user);

        if (! $entitlement->premiumEnforcementActive) {
            return null;
        }

        $limits = $this->limitsForTier($entitlement->tier);
        $now = CarbonImmutable::now();
        $multiplier = $this->multiplier();

        $candidate = null;
        $candidateRatio = 0.0;

        foreach (['rolling', 'weekly'] as $window) {
            $windowConfig = $limits[$window];
            $limit = (float) $windowConfig['limit'];

            if ($limit <= 0) {
                continue; // @codeCoverageIgnore
            }

            $periodStart = $this->periodStart($now, $window, $windowConfig);
            $cost = $this->getCostForPeriod($user, $periodStart, $now);

            $ratio = $cost / $limit;
            if ($ratio < self::WARNING_THRESHOLD) {
                continue;
            }

            if ($ratio <= $candidateRatio) {
                continue;
            }

            $resetsAt = $this->periodEnd($user, $now, $window, $windowConfig, $periodStart);
            $candidateRatio = $ratio;
            $candidate = new CreditWarning(
                limitType: $window,
                tier: $entitlement->tier,
                currentCredits: (int) round($cost * $multiplier),
                limitCredits: (int) round($limit * $multiplier),
                percentage: min(100, (int) floor($ratio * 100)),
                resetsAt: $resetsAt,
                resetsIn: $this->formatResetsIn($resetsAt),
            );
        }

        return $candidate;
    }

    /**
     * @return array{rolling: array{limit: float, period_hours: int}, weekly: array{limit: float, period_days: int}}
     */
    private function limitsForTier(SubscriptionTier $tier): array
    {
        /** @var array<string, array{rolling: array{limit: float, period_hours: int}, weekly: array{limit: float, period_days: int}}> $tierLimits */
        $tierLimits = config()->array('plate.tier_limits', []);

        return $tierLimits[$tier->value] ?? $tierLimits[SubscriptionTier::Free->value];
    }

    /**
     * @param  array<string, float|int>  $windowConfig
     */
    private function periodStart(CarbonImmutable $now, string $window, array $windowConfig): CarbonImmutable
    {
        return match ($window) {
            'rolling' => $now->subHours((int) $windowConfig['period_hours']),
            'weekly' => $now->subDays((int) $windowConfig['period_days']),
            default => $now,
        };
    }

    /**
     * @param  array<string, float|int>  $windowConfig
     */
    private function periodEnd(User $user, CarbonImmutable $now, string $window, array $windowConfig, CarbonImmutable $periodStart): CarbonImmutable
    {
        $oldest = AiUsage::query()
            ->forUser($user)
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<=', $now)
            ->min('created_at');

        $oldestAt = is_string($oldest) ? CarbonImmutable::parse($oldest) : $now;

        return match ($window) {
            'rolling' => $oldestAt->addHours((int) $windowConfig['period_hours']),
            'weekly' => $oldestAt->addDays((int) $windowConfig['period_days']),
            default => $now,
        };
    }

    private function getCostForPeriod(User $user, CarbonImmutable $start, CarbonImmutable $end): float
    {
        return (float) AiUsage::query()
            ->forUser($user)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->sum('cost');
    }

    private function multiplier(): int
    {
        return config()->integer('plate.credit_multiplier', 1);
    }

    private function formatResetsIn(CarbonInterface $resetTime): string
    {
        $diff = CarbonImmutable::now()->diff($resetTime);

        if ($diff->d > 0) {
            return $diff->d.' days '.$diff->h.' hours';
        }

        if ($diff->h > 0) {
            return $diff->h.' hours '.$diff->i.' minutes';
        }

        return $diff->i.' minutes'; // @codeCoverageIgnore
    }
}
