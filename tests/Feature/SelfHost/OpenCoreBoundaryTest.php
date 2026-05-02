<?php

declare(strict_types=1);

use App\Actions\Billing\AuthorizeGatedFeature;
use App\Contracts\Billing\ResolvesUserTier;
use App\Contracts\Memory\DispatchesMemoryExtraction;
use App\Contracts\Memory\ManagesMemoryContext;
use App\Contracts\Memory\PullsConversationHistory;
use App\Enums\GatedFeature;
use App\Enums\SubscriptionTier;
use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\Memory\NullConversationHistoryPuller;
use App\Services\Memory\NullMemoryExtractionDispatcher;
use App\Services\Memory\NullMemoryPromptContext;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Subscription;

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

it('resolves tiers from Stripe even when the legacy is_verified flag is true', function (): void {
    $user = User::factory()->verified()->create();

    expect(resolve(ResolvesUserTier::class)->resolve($user)->tier)->toBe(SubscriptionTier::Free);

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    expect(resolve(ResolvesUserTier::class)->resolve($user->fresh())->tier)->toBe(SubscriptionTier::Basic);
});

it('does not honor is_verified for new feature gates', function (): void {
    $user = User::factory()->verified()->create();

    expect(resolve(AuthorizeGatedFeature::class)->check($user, GatedFeature::MealPlanner))->toBeFalse()
        ->and(resolve(AuthorizeGatedFeature::class)->check($user, GatedFeature::Memory))->toBeFalse();
});

it('keeps null memory implementations available as community-safe fallbacks', function (): void {
    expect(class_exists(NullMemoryPromptContext::class))->toBeTrue()
        ->and(class_exists(NullMemoryExtractionDispatcher::class))->toBeTrue()
        ->and(class_exists(NullConversationHistoryPuller::class))->toBeTrue();

    expect(new NullMemoryPromptContext())
        ->toBeInstanceOf(ManagesMemoryContext::class);
    expect(new NullMemoryExtractionDispatcher())
        ->toBeInstanceOf(DispatchesMemoryExtraction::class);
    expect(new NullConversationHistoryPuller())
        ->toBeInstanceOf(PullsConversationHistory::class);
});

it('falls back to null memory implementations when the private package is absent', function (): void {
    $this->app->forgetInstance(ManagesMemoryContext::class);
    $this->app->forgetInstance(DispatchesMemoryExtraction::class);
    $this->app->forgetInstance(PullsConversationHistory::class);

    $this->app->bind(
        ManagesMemoryContext::class,
        NullMemoryPromptContext::class,
    );
    $this->app->bind(
        DispatchesMemoryExtraction::class,
        NullMemoryExtractionDispatcher::class,
    );
    $this->app->bind(
        PullsConversationHistory::class,
        NullConversationHistoryPuller::class,
    );

    expect(resolve(ManagesMemoryContext::class))
        ->toBeInstanceOf(NullMemoryPromptContext::class)
        ->and(resolve(DispatchesMemoryExtraction::class))
        ->toBeInstanceOf(NullMemoryExtractionDispatcher::class)
        ->and(resolve(PullsConversationHistory::class))
        ->toBeInstanceOf(NullConversationHistoryPuller::class);
});
