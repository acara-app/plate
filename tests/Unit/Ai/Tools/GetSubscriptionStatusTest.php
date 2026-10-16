<?php

declare(strict_types=1);

use App\Ai\Tools\GetSubscriptionStatus;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\TierEntitlement;
use App\Enums\SubscriptionTier;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

covers(GetSubscriptionStatus::class);

function subscriptionStatusFor(TierEntitlement $entitlement): array
{
    $tiers = new readonly class($entitlement) implements ResolvesUserTier
    {
        public function __construct(private TierEntitlement $entitlement) {}

        public function resolve(User $user): TierEntitlement
        {
            return $this->entitlement;
        }
    };

    test()->actingAs(User::factory()->create());

    return json_decode((new GetSubscriptionStatus($tiers))->handle(new Request), true);
}

beforeEach(function (): void {
    config(['app.url' => 'https://acara.example']);
});

it('describes itself as the tool to call for billing questions', function (): void {
    $tool = resolve(GetSubscriptionStatus::class);

    expect($tool->name())->toBe('get_subscription_status')
        ->and($tool->description())->toContain('cancellation')
        ->and($tool->schema(new TestJsonSchema))->toHaveKey('reason');
});

it('refuses when nobody is signed in', function (): void {
    expect(json_decode(resolve(GetSubscriptionStatus::class)->handle(new Request), true))
        ->toHaveKey('error');
});

it('reports the user plan and status with absolute links', function (TierEntitlement $entitlement, ?string $plan, string $status, ?string $accessEndsAt): void {
    $result = subscriptionStatusFor($entitlement);

    expect($result)->toMatchArray([
        'success' => true,
        'billing_enabled' => true,
        'has_plan' => $plan !== null,
        'plan' => $plan,
        'status' => $status,
        'access_ends_at' => $accessEndsAt,
        'billed_through' => 'acara_website',
        'manage_url' => 'https://acara.example/settings/billing',
        'plans_url' => 'https://acara.example/checkout/subscription',
        'support_url' => 'https://acara.example/support',
    ]);
})->with([
    'no plan' => [TierEntitlement::free(), null, 'none', null],
    'active Snap Pro' => [new TierEntitlement(SubscriptionTier::Snap), 'Snap Pro', 'active', null],
    'cancelled but paid through the period' => [
        new TierEntitlement(SubscriptionTier::Starter, gracePeriodEndsAt: CarbonImmutable::parse('2026-11-14 23:49:06', 'UTC')),
        'Starter', 'cancels_at_period_end', '2026-11-14T23:49:06+00:00',
    ],
    'payment still processing' => [TierEntitlement::paymentPending(), null, 'payment_pending', null],
]);

it('says there is nothing to manage when this instance does not sell plans', function (): void {
    expect(subscriptionStatusFor(TierEntitlement::unrestricted()))
        ->toMatchArray(['success' => true, 'billing_enabled' => false])
        ->not->toHaveKey('manage_url');
});
