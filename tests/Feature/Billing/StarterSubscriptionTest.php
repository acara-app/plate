<?php

declare(strict_types=1);

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\FoodAnalysisData;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Database\Seeders\SubscriptionProductSeeder;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

it('shows Starter at 2.99 with benefit-focused copy and no legacy plans', function (): void {
    $this->withoutVite();
    config()->set([
        'cashier.key' => 'pk_test_configured',
        'cashier.secret' => 'sk_test_configured',
        'cashier.webhook.secret' => 'whsec_configured',
    ]);
    $this->seed(SubscriptionProductSeeder::class);
    SubscriptionProduct::query()->where('name', 'Starter')->update(['stripe_price_id' => 'price_starter']);
    SubscriptionProduct::query()->where('name', 'Snap Pro')->update(['stripe_price_id' => 'price_snap_pro']);

    $this->get(route('checkout.subscription'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('products', 3)
            ->where('products.0.name', 'Free trial')
            ->where('products.1.name', 'Starter')
            ->where('products.1.formatted_price', '$2.99')
            ->where('products.1.features.0', 'Food photo scans for everyday tracking')
            ->where('products.2.name', 'Snap Pro')
            ->where('photoAllowance.offer.name', 'Starter'));
});

it('lets an active Starter subscriber scan without a monthly count limit', function (): void {
    $this->seed(SubscriptionProductSeeder::class);
    SubscriptionProduct::query()->where('name', 'Starter')->update(['stripe_price_id' => 'price_starter']);
    $user = User::factory()->create(['stripe_id' => 'cus_starter']);
    $user->subscriptions()->create([
        'type' => 'starter', 'stripe_id' => 'sub_starter', 'stripe_status' => 'active',
        'stripe_price' => 'price_starter', 'quantity' => 1,
    ]);
    $analysis = FoodAnalysisData::from([
        'items' => [[
            'name' => 'Rice', 'calories' => 130, 'protein' => 3, 'carbs' => 28, 'fat' => 0.3,
            'portion' => '100g', 'grams' => 100, 'match_name' => 'rice',
        ]],
        'total_calories' => 130, 'total_protein' => 3, 'total_carbs' => 28, 'total_fat' => 0.3, 'confidence' => 80,
    ]);
    $photos = resolve(ManagesPhotoAnalyses::class);

    $result = $photos->analyze(
        new PhotoAnalysisContext($user, null, (string) Str::uuid(), 'web', hash('sha256', 'meal')),
        fn (): FoodAnalysisData => $analysis,
    );

    expect($result)->toBe($analysis);
    expect($photos->entitlement($user)->limit)->toBeNull();
    expect($photos->entitlement($user)->mode)->toBe('premium');
});
