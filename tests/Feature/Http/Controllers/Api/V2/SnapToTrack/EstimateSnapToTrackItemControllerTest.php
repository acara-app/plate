<?php

declare(strict_types=1);

use App\Ai\Agents\FoodItemEstimatorAgent;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\TierEntitlement;
use App\Enums\SubscriptionTier;
use App\Http\Controllers\Api\V2\SnapToTrack\EstimateSnapToTrackItemController;
use App\Models\User;
use Illuminate\Support\Facades\Exceptions;
use Laravel\Sanctum\Sanctum;

covers(EstimateSnapToTrackItemController::class);

beforeEach(function (): void {
    app()->instance(ResolvesUserTier::class, new readonly class implements ResolvesUserTier
    {
        public function resolve(User $user): TierEntitlement
        {
            return new TierEntitlement(tier: SubscriptionTier::Free);
        }
    });

    $this->user = User::factory()->create();
});

it('requires a bearer token', function (): void {
    $this->postJson(route('api.v2.snap-to-track.estimate-item'), ['name' => 'Blueberries'])->assertUnauthorized();
});

it('estimates nutrition and a portion for a renamed ingredient', function (): void {
    FoodItemEstimatorAgent::fake([['calories' => 19.96, 'protein' => 0.25, 'carbs' => 5.07, 'fat' => 0.1, 'fiber' => 0.84, 'grams' => 35, 'portion' => ' 35 g (about ¼ cup) ']]);
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.snap-to-track.estimate-item'), ['name' => ' Blueberries ', 'grams' => 35])
        ->assertOk()
        ->assertExactJson(['calories' => 20, 'protein' => 0.3, 'carbs' => 5.1, 'fat' => 0.1, 'fiber' => 0.8, 'grams' => 35, 'portion' => '35 g (about ¼ cup)']);
});

it('requires a food name', function (): void {
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.snap-to-track.estimate-item'), ['grams' => 35])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('returns 402 without calling the AI when the credit budget is used up', function (): void {
    config()->set('plate.tier_limits.free', [
        'rolling' => ['limit' => 0.0, 'period_hours' => 24],
        'weekly' => ['limit' => 0.0, 'period_days' => 7],
    ]);
    FoodItemEstimatorAgent::fake(function (): void {
        throw new Exception('The estimator must not run when the credit budget is used up');
    });
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.snap-to-track.estimate-item'), ['name' => 'Blueberries', 'grams' => 35])
        ->assertStatus(402)
        ->assertJsonPath('error', 'usage_limit_exceeded');
});

it('reports a provider failure as unavailable', function (): void {
    Exceptions::fake();
    FoodItemEstimatorAgent::fake(function (): void {
        throw new RuntimeException('provider down');
    });
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.snap-to-track.estimate-item'), ['name' => 'Blueberries', 'grams' => 35])
        ->assertServiceUnavailable()
        ->assertExactJson(['error' => 'estimate_unavailable']);

    Exceptions::assertReported(RuntimeException::class);
});
