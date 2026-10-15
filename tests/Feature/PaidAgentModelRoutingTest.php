<?php

declare(strict_types=1);

use Acara\AcaraCore\Services\Ai\SubscriptionAgentModels;
use Acara\AcaraCore\Services\Billing\CloudPhotoAnalyses;
use App\Ai\Agents\AgentRunner;
use App\Ai\Agents\ConversationTitleGeneratorAgent;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Ai\Agents\MealPlanAgent;
use App\Ai\Agents\NutritionSpecialist;
use App\Contracts\Ai\ResolvesAgentModel;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoModel;
use App\Data\FoodAnalysisData;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Laravel\Cashier\Subscription;

beforeEach(function (): void {
    config()->set('plate.enable_premium_upgrades', true);
    config()->set('ai.agent_profiles.'.FoodPhotoAnalyzerAgent::class.'.paid', 'gemini-3.8-flash');
});

function subscribedModelUser(string $plan, string $status = 'active', ?string $end = null): User
{
    $product = SubscriptionProduct::factory()->create(['name' => $plan, 'stripe_price_id' => 'price_'.str_replace(' ', '_', $plan)]);
    $user = User::factory()->create(['stripe_id' => 'cus_'.fake()->uuid()]);
    Subscription::factory()->for($user)->withPrice($product->stripe_price_id)->create([
        'stripe_status' => $status, 'ends_at' => $end,
    ]);

    return $user;
}

it('gives each active paid plan the same upgraded profiles', function (string $plan): void {
    $user = subscribedModelUser($plan);
    $models = resolve(ResolvesAgentModel::class);

    expect($models)->toBeInstanceOf(SubscriptionAgentModels::class);
    expect($models->resolve(AgentRunner::class, $user))->model->toBe('gpt-6.1-sol');
    expect($models->resolve(NutritionSpecialist::class, $user))->provider->toBe('openai');
    expect($models->resolve(MealPlanAgent::class, $user))->model->toBe('gemini-3.8-flash');
    expect($models->resolve(FoodPhotoAnalyzerAgent::class, $user))->model->toBe('gemini-3.8-flash');
    expect($models->resolve(ConversationTitleGeneratorAgent::class, $user))->model->toBe('gpt-5-nano');
})->with(['Snap Pro', 'Supporter', 'Pro']);

it('honors subscription expiry and payment state', function (string $status, ?string $end, string $expected): void {
    $user = subscribedModelUser('Pro', $status, $end === null ? null : now()->modify($end)->toDateTimeString());

    expect(resolve(ResolvesAgentModel::class)->resolve(AgentRunner::class, $user))->model->toBe($expected);
})->with([
    'payment pending' => ['incomplete', null, 'gpt-5.4-mini'],
    'expired' => ['canceled', '-1 day', 'gpt-5.4-mini'],
    'valid cancellation grace' => ['canceled', '+1 day', 'gpt-6.1-sol'],
]);

it('isolates guests and free users from the paid model selection', function (): void {
    $paid = subscribedModelUser('Pro');
    $free = User::factory()->create();
    $models = resolve(ResolvesAgentModel::class);

    expect($models->resolve(AgentRunner::class, $paid))->model->toBe('gpt-6.1-sol');
    expect($models->resolve(AgentRunner::class, $free))->model->toBe('gpt-5.4-mini');
    expect($models->resolve(AgentRunner::class))->model->toBe('gpt-5.4-mini');
});

it('uses the configured paid model when the SDK invokes a specialist directly', function (): void {
    $paid = subscribedModelUser('Supporter');
    NutritionSpecialist::fake(['A meal suggestion']);

    resolve(NutritionSpecialist::class)->forModelUser($paid)->prompt('Suggest a meal');

    NutritionSpecialist::assertPrompted(fn ($prompt): bool => $prompt->model === 'gpt-6.1-sol' && $prompt->provider->name() === 'openai');
});

it('uses the paid photo model for legacy subscribers without adding scan quotas', function (string $plan): void {
    $user = subscribedModelUser($plan);
    $context = new PhotoAnalysisContext($user, null, 'test-request', 'test', 'fingerprint');
    $chosen = null;

    resolve(CloudPhotoAnalyses::class)->analyze($context, function (PhotoModel $model) use (&$chosen): FoodAnalysisData {
        $chosen = $model;

        return FoodAnalysisData::from([
            'items' => [], 'total_calories' => 0, 'total_protein' => 0,
            'total_carbs' => 0, 'total_fat' => 0, 'confidence' => 0,
        ]);
    });

    expect($chosen)->model->toBe('gemini-3.8-flash');
    $this->assertDatabaseCount('snap_photo_requests', 0);
})->with(['Supporter', 'Pro']);

it('freezes paid profiles in queued chat turns before an upgrade or cancellation', function (): void {
    Illuminate\Support\Facades\Queue::fake();
    $this->mock(App\Services\StreamEventStore::class)->shouldReceive('clear')->once();
    $user = subscribedModelUser('Pro');
    $conversation = App\Models\Conversation::factory()->create(App\Models\Conversation::participantAttributes($user));

    $this->actingAs($user)->postJson(route('chat.stream', $conversation->id), [
        'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello']]]],
    ])->assertAccepted();
    config()->set('ai.agent_profiles.'.AgentRunner::class.'.paid', 'gpt-6-sol');

    Illuminate\Support\Facades\Queue::assertPushed(App\Jobs\ProcessChatStream::class, function ($job): bool {
        $restored = unserialize(serialize($job));

        return $restored->agentModels[AgentRunner::class]['model'] === 'gpt-6.1-sol'
            && $restored->agentModels[NutritionSpecialist::class]['model'] === 'gpt-6.1-sol'
            && $restored->agentModels[MealPlanAgent::class]['model'] === 'gemini-3.8-flash';
    });
});

it('keeps Snap Pro scans idempotent while using the paid photo profile', function (): void {
    $user = subscribedModelUser('Snap Pro');
    $subscription = $user->subscriptions()->sole();
    Illuminate\Support\Facades\DB::table('snap_paid_periods')->insert([
        'subscription_id' => $subscription->stripe_id, 'customer_id' => $user->stripe_id,
        'invoice_id' => 'invoice_test', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(29),
    ]);
    $context = new PhotoAnalysisContext($user, null, 'test-idempotent-request', 'test', 'same-photo');
    $calls = 0;
    $analyze = function (PhotoModel $model) use (&$calls): FoodAnalysisData {
        $calls++;
        expect($model->model)->toBe('gemini-3.8-flash');

        return FoodAnalysisData::from([
            'items' => [['name' => 'egg', 'calories' => 70, 'protein' => 6, 'carbs' => 0, 'fat' => 5, 'portion' => 'one egg', 'grams' => 50]],
            'total_calories' => 70, 'total_protein' => 6, 'total_carbs' => 0, 'total_fat' => 5, 'confidence' => 0.8,
        ]);
    };
    $photos = resolve(CloudPhotoAnalyses::class);

    $photos->analyze($context, $analyze);
    $photos->analyze($context, $analyze);

    expect($calls)->toBe(1);
    $this->assertDatabaseCount('snap_photo_requests', 1);
    $this->assertDatabaseHas('snap_photo_requests', ['status' => 'succeeded']);
    expect($photos->entitlement($user, null))->used->toBe(1);
});

it('uses paid profiles for active subscriptions without a rollout switch', function (): void {
    $user = subscribedModelUser('Pro');

    expect(resolve(ResolvesAgentModel::class)->resolve(AgentRunner::class, $user))->model->toBe('gpt-6.1-sol');
});

it('offers Snap only when its model and billing dependencies are ready', function (string $model, bool $stripeReady, bool $purchasable, bool $expected): void {
    config()->set('ai.agent_profiles.'.FoodPhotoAnalyzerAgent::class.'.paid', $model);
    config()->set('cashier.key', $stripeReady ? 'pk_test_snap' : null);
    config()->set('cashier.secret', 'sk_test_snap');
    config()->set('cashier.webhook.secret', 'whsec_test_snap');
    SubscriptionProduct::factory()->create([
        'name' => 'Snap Pro', 'price' => 9, 'purchasable' => $purchasable,
        'stripe_lookup_key' => 'acara-plate-snap-pro-monthly-v1', 'stripe_price_id' => 'price_snap',
    ]);

    expect(resolve(CloudPhotoAnalyses::class)->enabled())->toBe($expected);
})->with([
    'ready' => ['gemini-3.8-flash', true, true, true],
    'unknown photo model' => ['missing-model', true, true, false],
    'Stripe unavailable' => ['gemini-3.8-flash', false, true, false],
    'product unavailable' => ['gemini-3.8-flash', true, false, false],
]);
