<?php

declare(strict_types=1);

use App\Ai\Agents\AgentRunner;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Ai\Agents\GroceryListAgent;
use App\Contracts\Billing\ProvidesAiBudget;
use App\Models\AiUsage;
use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\Billing\MonthlyAiBudget;
use Carbon\CarbonImmutable;
use Laravel\Cashier\Subscription;

covers(MonthlyAiBudget::class);

beforeEach(function (): void {
    config([
        'cashier.key' => 'pk_test_fake',
        'cashier.secret' => 'sk_test_fake',
        'cashier.webhook.secret' => 'whsec_test',
    ]);
    SubscriptionProduct::factory()->create([
        'name' => 'Starter', 'price' => 2.99, 'stripe_price_id' => 'price_starter',
        'stripe_lookup_key' => 'acara-plate-starter-monthly-v1', 'purchasable' => true,
    ]);

    $this->travelTo(CarbonImmutable::parse('2026-11-15 12:00:00', 'UTC'));
});

it("measures a free member's other AI spend this month against the monthly allowance", function (): void {
    config(['snap.other_ai_monthly_usd' => 0.75]);
    $member = User::factory()->create();
    AiUsage::factory()->for($member)->create(['agent' => AgentRunner::class, 'cost' => 0.12, 'created_at' => now()->subDays(3)]);
    AiUsage::factory()->for($member)->create(['agent' => GroceryListAgent::class, 'cost' => 0.08, 'created_at' => now()->startOfMonth()]);
    AiUsage::factory()->for($member)->create(['agent' => FoodPhotoAnalyzerAgent::class, 'cost' => 0.30, 'created_at' => now()]);
    AiUsage::factory()->for($member)->create(['agent' => AgentRunner::class, 'cost' => 0.40, 'created_at' => now()->subMonth()]);
    AiUsage::factory()->create(['agent' => AgentRunner::class, 'cost' => 0.50, 'created_at' => now()]);

    $budget = resolve(ProvidesAiBudget::class)->forUser($member);

    expect($budget?->used)->toEqualWithDelta(0.20, 0.000001)
        ->and($budget?->limit)->toBe(0.75)
        ->and($budget?->resetsAt->toIso8601String())->toBe('2026-12-01T00:00:00+00:00');
});

it('leaves paying members on their plan limits instead of the free allowance', function (): void {
    $member = User::factory()->create();
    Subscription::factory()->for($member)->active()->withPrice('price_starter')->create();

    expect(resolve(ProvidesAiBudget::class)->forUser($member))->toBeNull();
});

it('has no free allowance while paid photo plans cannot be sold', function (): void {
    config(['cashier.secret' => null]);

    expect(resolve(ProvidesAiBudget::class)->forUser(User::factory()->create()))->toBeNull();
});

it('accepts a whole-dollar monthly allowance', function (): void {
    config(['snap.other_ai_monthly_usd' => 1]);

    expect(resolve(ProvidesAiBudget::class)->forUser(User::factory()->create())?->limit)->toBe(1.0);
});
