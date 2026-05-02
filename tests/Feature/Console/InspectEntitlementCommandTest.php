<?php

declare(strict_types=1);

use App\Console\Commands\InspectEntitlementCommand;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Subscription;

covers(InspectEntitlementCommand::class);

beforeEach(function (): void {
    Config::set('plate.enable_premium_upgrades', true);

    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'stripe_price_id' => 'price_basic_monthly',
        'yearly_stripe_price_id' => 'price_basic_yearly',
    ]);
});

it('reports a free user with no subscription', function (): void {
    $user = User::factory()->create(['email' => 'free@example.com']);

    $this->artisan('billing:inspect-entitlement', ['user' => $user->email])
        ->expectsOutputToContain('User #'.$user->id)
        ->expectsOutputToContain('free')
        ->assertSuccessful();
});

it('reports the resolved tier for a paid user', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    $this->artisan('billing:inspect-entitlement', ['user' => (string) $user->id])
        ->expectsOutputToContain('basic')
        ->assertSuccessful();
});

it('reports the rollout reason when premium is gated by allowlist', function (): void {
    Config::set('plate.enable_premium_upgrades', false);
    Config::set('plate.premium_rollout.allowlist', ['staff@example.com']);

    $user = User::factory()->create(['email' => 'staff@example.com']);

    $this->artisan('billing:inspect-entitlement', ['user' => 'staff@example.com'])
        ->expectsOutputToContain('allowlist')
        ->assertSuccessful();
});

it('returns a non-zero exit code for an unknown user', function (): void {
    $this->artisan('billing:inspect-entitlement', ['user' => 'nobody@example.com'])
        ->expectsOutputToContain('No user matching')
        ->assertExitCode(1);
});

it('emits machine-readable JSON when requested', function (): void {
    $user = User::factory()->create();

    $exitCode = $this->artisan('billing:inspect-entitlement', [
        'user' => (string) $user->id,
        '--json' => true,
    ])->run();

    expect($exitCode)->toBe(0);
});
