<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Billing\ResolveAiAllowance;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\AiAllowance;
use App\Models\User;
use App\Services\Billing\PhotoPlanCatalog;
use App\Services\Billing\StripeBilling;
use Carbon\CarbonInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Cashier\Subscription;

/**
 * @codeCoverageIgnore
 */
#[Description("Inspect a user's tier, premium-rollout decision, subscription state, and current usage")]
#[Signature('billing:inspect-entitlement
        {user : User id or email address}
        {--json : Output as JSON instead of a human-readable table}')]
final class InspectEntitlementCommand extends Command
{
    public function handle(
        ResolvesUserTier $resolver,
        StripeBilling $stripeBilling,
        PhotoPlanCatalog $photoPlans,
        ResolveAiAllowance $allowances,
    ): int {
        $identifier = (string) $this->argument('user');
        $user = $this->findUser($identifier);

        if (! $user instanceof User) {
            $this->error(sprintf('No user matching "%s".', $identifier));

            return self::FAILURE;
        }

        $entitlement = $resolver->resolve($user);
        $allowance = $allowances->handle($user);
        $subscriptionLine = $this->describeSubscription($user);

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
                'billing_enabled' => $stripeBilling->isConfigured(),
                'selling_photo_plans' => $photoPlans->isSelling(),
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
                'ai_allowance' => $allowance instanceof AiAllowance ? [
                    'used_usd' => $allowance->used,
                    'limit_usd' => $allowance->limit,
                    ...$allowance->toArray(),
                ] : null,
            ], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info(sprintf('User #%d <%s>', $user->id, $user->email));

        $this->newLine();
        $this->line('Billing');
        $this->table(['Field', 'Value'], [
            ['Stripe configured', $stripeBilling->isConfigured() ? 'yes' : 'no'],
            ['Selling photo plans', $photoPlans->isSelling() ? 'yes' : 'no'],
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
        $this->line('AI allowance');
        $this->table(
            ['Resets', 'Used', 'Limit', '% used', 'Resets at', 'Used up'],
            $allowance instanceof AiAllowance ? [[
                $allowance->resets->value,
                sprintf('$%.4f', $allowance->used),
                sprintf('$%.2f', $allowance->limit),
                $allowance->percentage.'%',
                $allowance->resetsAt->toIso8601String(),
                $allowance->exhausted ? 'yes' : 'no',
            ]] : [['unrestricted', '—', '—', '—', '—', 'no']],
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
