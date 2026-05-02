<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Contracts\Billing\ResolvesUserTier;
use App\Contracts\Telemetry\EmitsPaywallEvents;
use App\Data\Billing\CreditWarning;
use App\Enums\SubscriptionTier;
use App\Enums\Telemetry\PaywallEvent;
use App\Models\AiUsage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

final readonly class BuildCreditWarning
{
    private const float WARNING_THRESHOLD = 0.80;

    public function __construct(private EmitsPaywallEvents $telemetry) {}

    public function handle(User $user): ?CreditWarning
    {
        $entitlement = resolve(ResolvesUserTier::class)->resolve($user);

        if (! $entitlement->premiumEnforcementActive) {
            return null;
        }

        $limits = $this->limitsForTier($entitlement->tier);
        $now = CarbonImmutable::now();
        $multiplier = $this->multiplier();

        $candidate = null;
        $candidatePercentage = 0;
        $candidateWindow = null;

        foreach (['rolling', 'weekly', 'monthly'] as $window) {
            $windowConfig = $limits[$window];
            $limit = (float) $windowConfig['limit'];

            if ($limit <= 0) {
                continue;
            }

            $cost = $this->getCostForPeriod(
                $user,
                $this->periodStart($now, $window, $windowConfig),
                $now,
            );

            $ratio = $cost / $limit;
            if ($ratio < self::WARNING_THRESHOLD) {
                continue;
            }

            if ($ratio >= 1.0) {
                continue;
            }

            $percentage = min(99, (int) floor($ratio * 100));

            if ($percentage <= $candidatePercentage) {
                continue;
            }

            $resetsAt = $this->periodEnd($now, $window, $windowConfig);
            $candidatePercentage = $percentage;
            $candidateWindow = $window;
            $candidate = new CreditWarning(
                limitType: $window,
                tier: $entitlement->tier,
                currentCredits: (int) round($cost * $multiplier),
                limitCredits: (int) round($limit * $multiplier),
                percentage: $percentage,
                resetsAt: $resetsAt,
                resetsIn: $this->formatResetsIn($resetsAt),
            );
        }

        if (! $candidate instanceof CreditWarning || $candidateWindow === null) {
            return null;
        }

        $cacheKey = $this->cacheKey($user, $candidate->limitType);

        if (Cache::has($cacheKey)) {
            return null;
        }

        Cache::put(
            $cacheKey,
            true,
            $this->ttlForWindow($candidateWindow, $limits[$candidateWindow]),
        );

        $this->telemetry->emit(
            event: PaywallEvent::CreditWarningShown,
            user: $user,
            payload: [
                'tier_current' => $candidate->tier->value,
                'limit_type' => $candidate->limitType,
                'percentage' => $candidate->percentage,
                'period_resets_at' => $candidate->resetsAt->toIso8601String(),
            ],
        );

        return $candidate;
    }

    /**
     * @return array{rolling: array{limit: float, period_hours: int}, weekly: array{limit: float, period_days: int}, monthly: array{limit: float, period_days: int}}
     */
    private function limitsForTier(SubscriptionTier $tier): array
    {
        /** @var array<string, array{rolling: array{limit: float, period_hours: int}, weekly: array{limit: float, period_days: int}, monthly: array{limit: float, period_days: int}}> $tierLimits */
        $tierLimits = config('plate.tier_limits', []); // @phpstan-ignore-line

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
            'monthly' => $now->subDays((int) $windowConfig['period_days']),
            default => $now,
        };
    }

    /**
     * @param  array<string, float|int>  $windowConfig
     */
    private function periodEnd(CarbonImmutable $now, string $window, array $windowConfig): CarbonImmutable
    {
        return match ($window) {
            'rolling' => $now->addHours((int) $windowConfig['period_hours']),
            'weekly' => $now->addDays((int) $windowConfig['period_days']),
            'monthly' => $now->addDays((int) $windowConfig['period_days']),
            default => $now,
        };
    }

    /**
     * @param  array<string, float|int>  $windowConfig
     */
    private function ttlForWindow(string $window, array $windowConfig): CarbonInterface
    {
        return match ($window) {
            'rolling' => CarbonImmutable::now()->addHours((int) $windowConfig['period_hours']),
            'weekly' => CarbonImmutable::now()->addDays((int) $windowConfig['period_days']),
            'monthly' => CarbonImmutable::now()->addDays((int) $windowConfig['period_days']),
            default => CarbonImmutable::now()->addDay(),
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
        return (int) config('plate.credit_multiplier'); // @phpstan-ignore cast.int
    }

    private function cacheKey(User $user, string $limitType): string
    {
        return sprintf('credit_warning_shown:%d:%s', $user->id, $limitType);
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

        return $diff->i.' minutes';
    }
}
