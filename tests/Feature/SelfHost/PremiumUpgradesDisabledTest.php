<?php

declare(strict_types=1);

use App\Actions\Billing\BuildCreditWarning;
use App\Actions\Billing\EnforceAiUsageLimit;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Ai\Tools\AnalyzePhoto;
use App\Ai\Tools\CreateMealPlan;
use App\Contracts\Ai\GeneratesMealPlans;
use App\Contracts\Billing\ResolvesUserTier;
use App\Enums\AgentMode;
use App\Enums\ModelName;
use App\Enums\SubscriptionTier;
use App\Models\AiUsage;
use App\Models\Conversation;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Tools\Request;
use Laravel\Cashier\Subscription;

beforeEach(function (): void {
    Config::set('plate.enable_premium_upgrades', false);
});

it('does not block AI invocations when the flag is off, even for users far over their cap', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 50.0,
    ]);

    resolve(EnforceAiUsageLimit::class)->handle($user, ModelName::GPT_5_4_MINI);

    expect(true)->toBeTrue();
});

it('does not gate the meal planner when the flag is off', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'), ['duration_days' => 3]);

    $response->assertRedirect();

    expect($user->mealPlans()->count())->toBe(1);
});

it('does not gate the meal planner UI when the flag is off', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('meal-plans.index'));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('proModelUpsell', false)
            ->where('entitlement.premium_enforcement_active', false)
            ->where('entitlement.tier', 'free')
        );
});

it('lets the AnalyzePhoto tool run for free users when the flag is off', function (): void {
    FoodPhotoAnalyzerAgent::fake([
        [
            'items' => [],
            'total_calories' => 0,
            'total_protein' => 0,
            'total_carbs' => 0,
            'total_fat' => 0,
            'confidence' => 0,
        ],
    ]);

    $user = User::factory()->create();
    Auth::login($user);

    $tool = new AnalyzePhoto([new Base64Image(base64_encode('img'), 'image/jpeg')]);
    $result = json_decode($tool->handle(new Request(['query' => 'analyze'])), true);

    expect($result)->toHaveKey('total_calories')
        ->and($result)->not->toHaveKey('error');
});

it('lets the CreateMealPlan tool run for free users when the flag is off', function (): void {
    $generator = Mockery::mock(GeneratesMealPlans::class);
    $generator->shouldReceive('handle')->once()->andReturnNull();
    $this->app->instance(GeneratesMealPlans::class, $generator);

    $user = User::factory()->create();
    Auth::login($user);

    $tool = new CreateMealPlan();
    $result = json_decode($tool->handle(new Request([])), true);

    expect($result)->not->toHaveKey('error');
});

it('returns no 402 from chat stream preflight when the flag is off', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 5.0,
    ]);

    $response = $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [
            ['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'hi']]],
        ],
        'mode' => AgentMode::Ask->value,
    ]);

    expect($response->getStatusCode())->not->toBe(402);
});

it('returns null from BuildCreditWarning when the flag is off', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 10.0,
    ]);

    $warning = resolve(BuildCreditWarning::class)->currentState($user);

    expect($warning)->toBeNull();
});

it('resolves an unrestricted entitlement even with an incomplete subscription when the flag is off', function (): void {
    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'stripe_price_id' => 'price_basic_monthly',
        'yearly_stripe_price_id' => 'price_basic_yearly',
    ]);

    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->incomplete()
        ->withPrice('price_basic_monthly')
        ->create();

    $entitlement = resolve(ResolvesUserTier::class)->resolve($user);

    expect($entitlement->tier)->toBe(SubscriptionTier::Free)
        ->and($entitlement->isUnrestricted())->toBeTrue()
        ->and($entitlement->premiumEnforcementActive)->toBeFalse()
        ->and($entitlement->isPaymentPending())->toBeFalse();
});

it('exposes premium_enforcement_active=false on the shared entitlement prop everywhere', function (): void {
    $user = User::factory()->create();

    $surfaces = [
        route('meal-plans.index'),
        route('billing.index'),
        route('user-profile.edit'),
    ];

    foreach ($surfaces as $url) {
        $this->actingAs($user)->get($url)
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('entitlement.premium_enforcement_active', false)
                ->where('enablePremiumUpgrades', false)
            );
    }
});

it('does not require subscription middleware to gate verified=null users when the flag is off', function (): void {
    $user = User::factory()->create(['is_verified' => false]);

    $response = $this->actingAs($user)->get(route('meal-plans.index'));

    $response->assertSuccessful();
});
