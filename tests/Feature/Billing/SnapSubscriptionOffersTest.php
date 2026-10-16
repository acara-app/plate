<?php

declare(strict_types=1);

use Acara\AcaraCore\Services\Billing\CloudPhotoAnalyses;
use Acara\AcaraCore\Services\Billing\SnapSubscriptionOffers;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoModel;
use App\Data\FoodAnalysisData;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Database\Seeders\SubscriptionProductSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

covers(CloudPhotoAnalyses::class, SnapSubscriptionOffers::class);

it('offers only the photo plans without rollout flags or a separate premium model', function (array $configOverrides, array $productAttributes, array $expectedPlans): void {
    $this->withoutVite();
    config()->set([
        'snap.enabled' => false,
        'snap.model_approved' => false,
        'snap.premium.provider' => null,
        'snap.premium.model' => null,
        'cashier.key' => 'pk_test_configured',
        'cashier.secret' => 'sk_test_configured',
        'cashier.webhook.secret' => 'whsec_configured',
        ...$configOverrides,
    ]);
    $this->seed(SubscriptionProductSeeder::class);
    SubscriptionProduct::query()->where('name', 'Snap Pro')->firstOrFail()->update([
        'stripe_price_id' => 'price_snap_pro',
        ...$productAttributes,
    ]);

    $this->get(route('checkout.subscription'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('photoPlan', null)
            ->where('products', function (Collection $products) use ($expectedPlans): bool {
                expect($products->pluck('name')->all())->toEqualCanonicalizing($expectedPlans);

                return true;
            }));
})->with([
    'configured Stripe plan' => [[], [], ['Free trial', 'Snap Pro']],
    'missing Stripe key' => [['cashier.key' => null], [], ['Free trial']],
    'missing Stripe secret' => [['cashier.secret' => null], [], ['Free trial']],
    'missing webhook secret' => [['cashier.webhook.secret' => null], [], ['Free trial']],
    'missing price' => [[], ['stripe_price_id' => null], ['Free trial']],
    'unavailable product' => [[], ['purchasable' => false], ['Free trial']],
    'invalid paid model' => [['ai.agent_profiles.'.FoodPhotoAnalyzerAgent::class.'.paid' => 'unregistered-model'], [], ['Free trial']],
    'paid model without vision' => [[
        'ai.agent_profiles.'.FoodPhotoAnalyzerAgent::class.'.paid' => 'claude-sonnet-5',
        'ai.model_catalog.claude-sonnet-5.capabilities' => ['structured_output'],
    ], [], ['Free trial']],
]);

it('completes a paid scan using the shared paid photo model profile', function (string $paidModel, string $expectedProvider): void {
    config()->set([
        'snap.enabled' => false,
        'snap.model_approved' => false,
        'snap.premium.provider' => 'obsolete-provider',
        'snap.premium.model' => 'obsolete-model',
        'snap.premium.max_tokens' => 4096,
        'plate.enable_premium_upgrades' => true,
        'ai.agent_profiles.'.FoodPhotoAnalyzerAgent::class.'.standard' => 'gemini-3.6-flash',
        'ai.agent_profiles.'.FoodPhotoAnalyzerAgent::class.'.paid' => $paidModel,
        'cashier.key' => 'pk_test_configured',
        'cashier.secret' => 'sk_test_configured',
        'cashier.webhook.secret' => 'whsec_configured',
    ]);
    $this->seed(SubscriptionProductSeeder::class);
    SubscriptionProduct::query()->where('name', 'Snap Pro')->firstOrFail()->update(['stripe_price_id' => 'price_snap_pro']);
    $user = User::factory()->create(['stripe_id' => 'cus_snap']);
    $user->subscriptions()->create([
        'type' => 'snap-pro',
        'stripe_id' => 'sub_snap',
        'stripe_status' => 'active',
        'stripe_price' => 'price_snap_pro',
        'quantity' => 1,
    ]);
    DB::table('snap_paid_periods')->insert([
        'subscription_id' => 'sub_snap',
        'customer_id' => 'cus_snap',
        'invoice_id' => 'in_snap',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);
    $analysis = FoodAnalysisData::from([
        'items' => [[
            'name' => 'Rice', 'calories' => 130, 'protein' => 3, 'carbs' => 28, 'fat' => 0.3,
            'portion' => '100g', 'grams' => 100, 'match_name' => 'rice',
        ]],
        'total_calories' => 130,
        'total_protein' => 3,
        'total_carbs' => 28,
        'total_fat' => 0.3,
        'confidence' => 80,
    ]);
    $photos = resolve(CloudPhotoAnalyses::class);

    $result = $photos->analyze(
        new PhotoAnalysisContext($user, null, (string) Str::uuid(), 'web', hash('sha256', 'meal')),
        function (PhotoModel $selectedModel) use ($analysis, $paidModel, $expectedProvider): FoodAnalysisData {
            expect($selectedModel->provider)->toBe($expectedProvider);
            expect($selectedModel->model)->toBe($paidModel);
            expect($selectedModel->maxTokens)->toBe(4096);

            return $analysis;
        },
    );

    expect($result)->toBe($analysis);
    $allowance = $photos->entitlement($user, null);
    expect($allowance->mode)->toBe('premium');
    expect($allowance->limit)->toBe(100);
    expect($allowance->used)->toBe(1);
})->with([
    'Gemini paid profile' => ['gemini-3.8-flash', 'gemini'],
    'alternate Gemini profile' => ['gemini-3.6-flash', 'gemini'],
    'OpenAI paid profile' => ['gpt-6-sol', 'openai'],
    'Anthropic paid profile' => ['claude-sonnet-5', 'anthropic'],
]);
