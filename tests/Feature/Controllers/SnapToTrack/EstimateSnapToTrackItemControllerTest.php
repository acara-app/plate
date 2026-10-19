<?php

declare(strict_types=1);

use App\Ai\Agents\FoodItemEstimatorAgent;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\TierEntitlement;
use App\Data\FoodItemEstimateData;
use App\Enums\SubscriptionTier;
use App\Http\Controllers\SnapToTrack\EstimateSnapToTrackItemController;
use App\Models\User;
use Illuminate\Support\Facades\Exceptions;
use Laravel\Ai\Prompts\AgentPrompt;

covers(EstimateSnapToTrackItemController::class, FoodItemEstimateData::class);

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

it('estimates nutrition and a new portion for a renamed item at the same weight', function (): void {
    FoodItemEstimatorAgent::fake([['calories' => 19.96, 'protein' => 0.25, 'carbs' => 5.07, 'fat' => 0.1, 'fiber' => 0.84, 'grams' => 35, 'portion' => ' 35 g (about ¼ cup) ']]);

    $this->actingAs($this->user)
        ->postJson(route('snap-to-track.estimate-item'), ['name' => ' Blueberries ', 'grams' => 35])
        ->assertOk()
        ->assertExactJson(['calories' => 20, 'protein' => 0.3, 'carbs' => 5.1, 'fat' => 0.1, 'fiber' => 0.8, 'grams' => 35, 'portion' => '35 g (about ¼ cup)']);
});

it('replaces a portion that named the old food and never sends that wording to the AI', function (): void {
    FoodItemEstimatorAgent::fake([['calories' => 14.3, 'protein' => 0.2, 'carbs' => 3.6, 'fat' => 0.1, 'grams' => 25, 'portion' => '25 g (about ⅙ cup)']]);

    $this->actingAs($this->user)
        ->postJson(route('snap-to-track.estimate-item'), ['name' => 'Blueberries', 'grams' => 25, 'portion' => '25g (approx. 6 olives)'])
        ->assertOk()
        ->assertJsonPath('grams', 25)
        ->assertJsonPath('portion', '25 g (about ⅙ cup)');

    FoodItemEstimatorAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Food: "Blueberries". Weight: 25 g.'));
    FoodItemEstimatorAgent::assertNotPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('olive'));
});

it('falls back to the weight when the AI gives no portion and keeps it within the saved length', function (string $aiPortion, string $expected): void {
    FoodItemEstimatorAgent::fake([['calories' => 14.3, 'protein' => 0.2, 'carbs' => 3.6, 'fat' => 0.1, 'grams' => 25, 'portion' => $aiPortion]]);

    $this->actingAs($this->user)
        ->postJson(route('snap-to-track.estimate-item'), ['name' => 'Blueberries', 'grams' => 25])
        ->assertOk()
        ->assertJsonPath('portion', $expected);
})->with([
    'no portion' => ['', '25 g'],
    'blank portion' => ['   ', '25 g'],
    'too long' => [str_repeat('a', 150), str_repeat('a', 100)],
]);

it('asks for the portion in the user language', function (): void {
    FoodItemEstimatorAgent::fake([['calories' => 14.3, 'protein' => 0.2, 'carbs' => 3.6, 'fat' => 0.1, 'grams' => 25, 'portion' => '25 g (environ ⅙ tasse)']]);

    $this->actingAs(User::factory()->create(['locale' => 'fr']))
        ->postJson(route('snap-to-track.estimate-item'), ['name' => 'Myrtilles', 'grams' => 25])
        ->assertOk();

    FoodItemEstimatorAgent::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->agent->instructions(), 'Write `portion` in Français'));
});

it('requires a food name', function (): void {
    $this->actingAs($this->user)
        ->postJson(route('snap-to-track.estimate-item'), ['grams' => 35])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('rejects guests', function (): void {
    $this->postJson(route('snap-to-track.estimate-item'), ['name' => 'Blueberries'])
        ->assertUnauthorized();
});

it('returns 402 and does not call the AI when the credit budget is used up', function (): void {
    config()->set('plate.tier_limits.free', [
        'rolling' => ['limit' => 0.0, 'period_hours' => 24],
        'weekly' => ['limit' => 0.0, 'period_days' => 7],
    ]);
    FoodItemEstimatorAgent::fake(function (): void {
        throw new Exception('The estimator must not run when the credit budget is used up');
    });

    $this->actingAs($this->user)
        ->postJson(route('snap-to-track.estimate-item'), ['name' => 'Blueberries', 'grams' => 35])
        ->assertStatus(402)
        ->assertJsonPath('error', 'usage_limit_exceeded');
});

it('reports a provider failure and keeps the user numbers', function (): void {
    Exceptions::fake();
    FoodItemEstimatorAgent::fake(function (): void {
        throw new RuntimeException('provider down');
    });

    $this->actingAs($this->user)
        ->postJson(route('snap-to-track.estimate-item'), ['name' => 'Blueberries', 'grams' => 35])
        ->assertServiceUnavailable()
        ->assertExactJson(['error' => 'estimate_unavailable']);

    Exceptions::assertReported(RuntimeException::class);
});
