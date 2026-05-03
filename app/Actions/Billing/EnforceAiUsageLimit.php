<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Contracts\Billing\ResolvesUserTier;
use App\Contracts\Telemetry\EmitsPaywallEvents;
use App\Enums\ModelName;
use App\Enums\SubscriptionTier;
use App\Enums\Telemetry\PaywallEvent;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Models\AiUsage;
use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class EnforceAiUsageLimit
{
    public function __construct(
        private ResolvesUserTier $resolveUserTier,
        private EmitsPaywallEvents $telemetry,
    ) {}

    /**
     * @throws UsageLimitExceededException when the projected total cost would exceed any window.
     */
    public function handle(User $user, ?ModelName $model = null): void
    {
        $entitlement = $this->resolveUserTier->resolve($user);

        if (! $entitlement->premiumEnforcementActive) {
            return;
        }

        $limits = $this->limitsForTier($entitlement->tier);
        $estimate = $this->estimateCallCost($model);
        $now = CarbonImmutable::now();
        $multiplier = $this->multiplier();

        foreach (['rolling', 'weekly', 'monthly'] as $window) {
            $windowConfig = $limits[$window];
            $periodStart = $this->periodStart($now, $window, $windowConfig);
            $periodEnd = $this->periodEnd($user, $now, $window, $windowConfig, $periodStart);
            $current = $this->getCostForPeriod($user, $periodStart, $now);
            $limit = (float) $windowConfig['limit'];

            if ($current + $estimate > $limit) {
                $this->telemetry->emit(
                    event: PaywallEvent::UsageLimitExceeded,
                    user: $user,
                    payload: [
                        'tier_current' => $entitlement->tier->value,
                        'limit_type' => $window,
                        'current_credits' => (int) round($current * $multiplier),
                        'limit_credits' => (int) round($limit * $multiplier),
                        'period_resets_at' => $periodEnd->toIso8601String(),
                    ],
                );

                throw new UsageLimitExceededException(
                    limitType: $window,
                    tier: $entitlement->tier,
                    currentCredits: (int) round($current * $multiplier),
                    limitCredits: (int) round($limit * $multiplier),
                    resetsAt: $periodEnd,
                );
            }
        }
    }

    /**
     * @return array{rolling: array{limit: float, period_hours: int}, weekly: array{limit: float, period_days: int}, monthly: array{limit: float, period_days: int}}
     */
    private function limitsForTier(SubscriptionTier $tier): array
    {
        /** @var array<string, array{rolling: array{limit: float, period_hours: int}, weekly: array{limit: float, period_days: int}, monthly: array{limit: float, period_days: int}}> $tierLimits */
        $tierLimits = config()->array('plate.tier_limits', []);

        return $tierLimits[$tier->value] ?? $tierLimits[SubscriptionTier::Free->value];
    }

    private function estimateCallCost(?ModelName $model): float
    {
        /** @var array{token_budget: array{input: int, output: int}, fallback_estimate: float} $config */
        $config = config()->array('plate.ai_usage_preflight', [
            'token_budget' => ['input' => 2_000, 'output' => 1_000],
            'fallback_estimate' => 0.01,
        ]);

        if (! $model instanceof ModelName) {
            return (float) $config['fallback_estimate'];
        }

        $pricing = $model->getPricing();
        $tokens = $config['token_budget'];

        return ((int) $tokens['input']) / 1_000_000 * $pricing['input']
            + ((int) $tokens['output']) / 1_000_000 * $pricing['output'];
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
    private function periodEnd(User $user, CarbonImmutable $now, string $window, array $windowConfig, CarbonImmutable $periodStart): CarbonImmutable
    {
        if ($window === 'rolling') {
            $hours = (int) $windowConfig['period_hours'];
            $oldest = AiUsage::query()
                ->forUser($user)
                ->where('created_at', '>=', $periodStart)
                ->where('created_at', '<=', $now)
                ->min('created_at');

            return is_string($oldest)
                ? CarbonImmutable::parse($oldest)->addHours($hours)
                : $now->addHours($hours);
        }

        return match ($window) {
            'weekly' => $now->addDays((int) $windowConfig['period_days']),
            'monthly' => $now->addDays((int) $windowConfig['period_days']),
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
}
