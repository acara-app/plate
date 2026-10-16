<?php

declare(strict_types=1);

use App\Models\SubscriptionProduct;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('shows the configured scan and shared credit limits when choosing a photo plan', function (string $name, string $tier): void {
    offerAllSubscriptions();
    config([
        "plate.snap_to_track.burst_caps.{$tier}" => 42,
        "plate.tier_limits.{$tier}.rolling" => ['limit' => 0.123, 'period_hours' => 12],
        "plate.tier_limits.{$tier}.weekly" => ['limit' => 0.987, 'period_days' => 5],
        'plate.credit_multiplier' => 1000,
    ]);
    $user = User::factory()->create(['stripe_id' => null]);
    SubscriptionProduct::factory()->create(['name' => $name, 'price' => 2.99]);

    $this->actingAs($user)->get(route('checkout.subscription'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('products', 2)
            ->where('products.0.name', $name)
            ->where('products.0.photo_limits.scans_per_hour', 42)
            ->where('products.0.photo_limits.rolling_credits', 123)
            ->where('products.0.photo_limits.rolling_hours', 12)
            ->where('products.0.photo_limits.weekly_credits', 987)
            ->where('products.0.photo_limits.weekly_days', 5));
})->with([
    'Starter' => ['Starter', 'starter'],
    'Snap Pro' => ['Snap Pro', 'snap'],
]);
