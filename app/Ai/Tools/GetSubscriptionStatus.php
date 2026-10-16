<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Attributes\AiToolSensitivity;
use App\Contracts\Billing\ResolvesUserTier;
use App\Enums\DataSensitivity;
use App\Enums\SubscriptionTier;
use App\Models\User;
use App\Utilities\StaticUrl;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::Personal)]
final readonly class GetSubscriptionStatus implements Tool
{
    public function __construct(private ResolvesUserTier $tiers) {}

    public function name(): string
    {
        return 'get_subscription_status';
    }

    public function description(): string
    {
        return "Retrieve the current user's Acara plan and billing status, with links to manage or cancel it and to contact support. Call this before answering any question about subscriptions, plans, charges, payments, cancellation, or refunds.";
    }

    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return json_encode(['error' => 'User not authenticated']) ?: '{"error":"User not authenticated"}';
        }

        $entitlement = $this->tiers->resolve($user);

        if ($entitlement->isUnrestricted()) {
            return json_encode([
                'success' => true,
                'billing_enabled' => false,
                'message' => 'This Acara instance does not sell plans, so there is nothing to manage or cancel.',
            ]) ?: '{"success":true,"billing_enabled":false}';
        }

        $hasPlan = $entitlement->tier !== SubscriptionTier::Free;
        $status = match (true) {
            $entitlement->isPaymentPending() => 'payment_pending',
            ! $hasPlan => 'none',
            $entitlement->inGracePeriod() => 'cancels_at_period_end',
            default => 'active',
        };

        return json_encode([
            'success' => true,
            'billing_enabled' => true,
            'has_plan' => $hasPlan,
            'plan' => $hasPlan ? $entitlement->tier->label() : null,
            'status' => $status,
            'access_ends_at' => $entitlement->gracePeriodEndsAt?->toIso8601String(),
            'billed_through' => 'acara_website',
            'manage_url' => StaticUrl::billingUrl(),
            'plans_url' => StaticUrl::checkoutUrl(),
            'support_url' => StaticUrl::supportUrl(),
        ]) ?: '{"success":true}';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'reason' => $schema->string()->required()->nullable()
                ->description('Optional short note on why the plan is being checked (e.g., "cancel request", "unexpected charge").'),
        ];
    }
}
