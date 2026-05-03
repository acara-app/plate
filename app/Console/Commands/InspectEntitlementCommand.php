<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GetAiUsageForBillingAction;
use App\Contracts\Billing\ResolvesUserTier;
use App\Models\User;
use App\Services\Billing\PremiumRolloutGate;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Laravel\Cashier\Subscription;

/**
 * @codeCoverageIgnore
 */
final class InspectEntitlementCommand extends Command
{
    protected $signature = 'billing:inspect-entitlement
        {user : User id or email address}
        {--json : Output as JSON instead of a human-readable table}';

    protected $description = "Inspect a user's tier, premium-rollout decision, subscription state, and current usage";

    public function handle(
        ResolvesUserTier $resolver,
        PremiumRolloutGate $rolloutGate,
        GetAiUsageForBillingAction $usage,
    ): int {
        $identifier = (string) $this->argument('user');
        $user = $this->findUser($identifier);

        if (! $user instanceof User) {
            $this->error(sprintf('No user matching "%s".', $identifier));

            return self::FAILURE;
        }

        $entitlement = $resolver->resolve($user);
        $usageSnapshot = $usage->handle($user);
        $rolloutReason = $rolloutGate->reasonFor($user);
        $subscriptionLine = $this->describeSubscription($user);

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
                'rollout_reason' => $rolloutReason,
                'rollout_active' => $rolloutGate->isActiveFor($user),
                'entitlement' => [
                    'tier' => $entitlement->tier->value,
                    'premium_enforcement_active' => $entitlement->premiumEnforcementActive,
                    'payment_pending' => $entitlement->isPaymentPending(),
                    'on_grace_period' => $entitlement->inGracePeriod(),
                    'grace_period_ends_at' => $entitlement->gracePeriodEndsAt instanceof CarbonInterface
                        ? $entitlement->gracePeriodEndsAt->toIso8601String() // @codeCoverageIgnore
                        : null,
                ],
                'subscription' => $subscriptionLine,
                'usage' => [
                    'rolling' => $usageSnapshot['rolling'],
                    'weekly' => $usageSnapshot['weekly'],
                ],
            ], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info(sprintf('User #%d <%s>', $user->id, $user->email));

        $this->newLine();
        $this->line('Premium rollout');
        $this->table(['Field', 'Value'], [
            ['Reason', $rolloutReason],
            ['Active', $rolloutGate->isActiveFor($user) ? 'yes' : 'no'],
            ['Global flag', config()->boolean('plate.enable_premium_upgrades', false) ? 'on' : 'off'],
            ['Allowlist size', count(config()->array('plate.premium_rollout.allowlist', []))],
            ['Percentage cohort', config()->integer('plate.premium_rollout.percentage', 0).'%'],
        ]);

        $this->newLine();
        $this->line('Resolved entitlement');
        $this->table(['Field', 'Value'], [
            ['Tier', $entitlement->tier->value],
            ['Tier label', $entitlement->tier->label()],
            ['Premium enforcement', $entitlement->premiumEnforcementActive ? 'on' : 'off'],
            ['Payment pending', $entitlement->isPaymentPending() ? 'yes' : 'no'],
            ['On grace period', $entitlement->inGracePeriod() ? 'yes' : 'no'],
            ['Grace ends at', $entitlement->gracePeriodEndsAt instanceof CarbonInterface
                ? $entitlement->gracePeriodEndsAt->toIso8601String() // @codeCoverageIgnore
                : '—'],
        ]);

        $this->newLine();
        $this->line('Subscription state');
        $this->table(['Field', 'Value'], $subscriptionLine);

        $this->newLine();
        $this->line('Current usage');
        $this->table(
            ['Window', 'Used', 'Limit', '% of limit', 'Resets in', 'Over limit'],
            [
                ['rolling', $usageSnapshot['rolling']['current'], $usageSnapshot['rolling']['limit'], $usageSnapshot['rolling']['percentage'].'%', $usageSnapshot['rolling']['resets_in'], $usageSnapshot['rolling']['over_limit'] ? 'yes' : 'no'],
                ['weekly', $usageSnapshot['weekly']['current'], $usageSnapshot['weekly']['limit'], $usageSnapshot['weekly']['percentage'].'%', $usageSnapshot['weekly']['resets_in'], $usageSnapshot['weekly']['over_limit'] ? 'yes' : 'no'],
            ],
        );

        return self::SUCCESS;
    }

    private function findUser(string $identifier): ?User
    {
        if (ctype_digit($identifier)) {
            return User::query()->find((int) $identifier);
        }

        return User::query()->where('email', $identifier)->first();
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function describeSubscription(User $user): array
    {
        $subscription = $user->subscriptions()->latest()->first();

        if (! $subscription instanceof Subscription) {
            return [['Has subscription', 'no']];
        }

        return [
            ['Has subscription', 'yes'],
            ['Stripe ID', (string) $subscription->stripe_id],
            ['Status', (string) $subscription->stripe_status],
            ['Stripe price', is_string($subscription->stripe_price) && $subscription->stripe_price !== '' ? $subscription->stripe_price : (is_string($subscription->items()->value('stripe_price')) ? $subscription->items()->value('stripe_price') : '—')],
            ['Ends at', $subscription->ends_at instanceof CarbonInterface
                ? $subscription->ends_at->toIso8601String() // @codeCoverageIgnore
                : '—'],
            ['On grace period', $subscription->onGracePeriod() ? 'yes' : 'no'],
            ['Active', $subscription->active() ? 'yes' : 'no'],
            ['Incomplete', $subscription->incomplete() ? 'yes' : 'no'],
        ];
    }
}
