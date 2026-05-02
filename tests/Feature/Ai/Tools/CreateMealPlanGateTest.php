<?php

declare(strict_types=1);

use App\Ai\Tools\CreateMealPlan;
use App\Contracts\Ai\GeneratesMealPlans;
use App\Models\AiUsage;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Ai\Tools\Request;
use Laravel\Cashier\Subscription;

covers(CreateMealPlan::class);

beforeEach(function (): void {
    Config::set('plate.enable_premium_upgrades', true);

    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'stripe_price_id' => 'price_basic_monthly',
        'yearly_stripe_price_id' => 'price_basic_yearly',
    ]);

    SubscriptionProduct::factory()->create([
        'name' => 'Plus',
        'stripe_price_id' => 'price_plus_monthly',
        'yearly_stripe_price_id' => 'price_plus_yearly',
    ]);

    $this->generator = new class implements GeneratesMealPlans
    {
        public array $calls = [];

        public function handle(User $user, int $totalDays = 7, ?string $customPrompt = null): void
        {
            $this->calls[] = ['user' => $user->id, 'totalDays' => $totalDays];
        }
    };

    app()->instance(GeneratesMealPlans::class, $this->generator);
});

it('returns a paywall payload for free users without invoking the generator', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $tool = new CreateMealPlan();
    $result = json_decode($tool->handle(new Request(['total_days' => 3])), true);

    expect($result)->toMatchArray([
        'error' => 'feature_gated',
        'feature' => 'meal_planner',
        'required_tier' => 'basic',
        'required_tier_label' => 'Basic',
    ]);

    expect($this->generator->calls)->toBeEmpty()
        ->and(AiUsage::query()->count())->toBe(0);
});

it('allows Basic users to invoke the generator', function (): void {
    $user = User::factory()->create();
    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    $this->actingAs($user);

    $tool = new CreateMealPlan();
    $result = json_decode($tool->handle(new Request(['total_days' => 3])), true);

    expect($result)->toHaveKey('success', true)
        ->and($this->generator->calls)->toHaveCount(1);
});

it('allows free users when premium enforcement is off', function (): void {
    Config::set('plate.enable_premium_upgrades', false);

    $user = User::factory()->create();
    $this->actingAs($user);

    $tool = new CreateMealPlan();
    $result = json_decode($tool->handle(new Request(['total_days' => 3])), true);

    expect($result)->toHaveKey('success', true)
        ->and($this->generator->calls)->toHaveCount(1);
});
