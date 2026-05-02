<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Ai\Tools\AnalyzePhoto;
use App\Models\AiUsage;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Tools\Request;
use Laravel\Cashier\Subscription;

use function Pest\Laravel\actingAs;

covers(AnalyzePhoto::class);

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
});

it('returns a paywall payload for free users without invoking the agent', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $image = new Base64Image(base64_encode('fake-image-data'), 'image/jpeg');
    $tool = new AnalyzePhoto([$image]);

    $result = json_decode($tool->handle(new Request(['query' => 'What is this?'])), true);

    expect($result)->toMatchArray([
        'error' => 'feature_gated',
        'feature' => 'image_analysis',
        'required_tier' => 'basic',
        'required_tier_label' => 'Basic',
    ]);

    expect(AiUsage::query()->count())->toBe(0);
});

it('allows Basic users to invoke the agent', function (): void {
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
    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    actingAs($user);

    $image = new Base64Image(base64_encode('fake-image-data'), 'image/jpeg');
    $tool = new AnalyzePhoto([$image]);

    $result = json_decode($tool->handle(new Request(['query' => 'analyze'])), true);

    expect($result)->toHaveKey('total_calories')
        ->and($result)->not->toHaveKey('error');
});

it('allows Plus users to invoke the agent', function (): void {
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
    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_plus_monthly')
        ->create();

    actingAs($user);

    $image = new Base64Image(base64_encode('fake-image-data'), 'image/jpeg');
    $tool = new AnalyzePhoto([$image]);

    $result = json_decode($tool->handle(new Request(['query' => 'analyze'])), true);

    expect($result)->toHaveKey('total_calories')
        ->and($result)->not->toHaveKey('error');
});

it('allows free users when premium enforcement is off', function (): void {
    Config::set('plate.enable_premium_upgrades', false);

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
    actingAs($user);

    $image = new Base64Image(base64_encode('fake-image-data'), 'image/jpeg');
    $tool = new AnalyzePhoto([$image]);

    $result = json_decode($tool->handle(new Request(['query' => 'analyze'])), true);

    expect($result)->toHaveKey('total_calories')
        ->and($result)->not->toHaveKey('error');
});
