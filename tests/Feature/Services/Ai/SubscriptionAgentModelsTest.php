<?php

declare(strict_types=1);

use App\Ai\Agents\ConversationTitleGeneratorAgent;
use App\Ai\Agents\NutritionSpecialist;
use App\Contracts\Ai\ResolvesAgentModel;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\TierEntitlement;
use App\Enums\SubscriptionTier;
use App\Models\User;
use App\Services\Ai\SubscriptionAgentModels;
use Illuminate\Support\Facades\Exceptions;

covers(SubscriptionAgentModels::class);

beforeEach(function (): void {
    config()->set('ai.agent_profiles.'.NutritionSpecialist::class, ['standard' => 'gemini-3.6-flash', 'paid' => 'gpt-6.1-sol']);
    config()->set('ai.agent_profiles.'.ConversationTitleGeneratorAgent::class, ['standard' => 'gpt-5-nano']);
});

function modelResolvedFor(TierEntitlement $entitlement, string $agentClass): string
{
    app()->instance(ResolvesUserTier::class, new readonly class($entitlement) implements ResolvesUserTier
    {
        public function __construct(private TierEntitlement $entitlement) {}

        public function resolve(User $user): TierEntitlement
        {
            return $this->entitlement;
        }
    });

    return resolve(ResolvesAgentModel::class)->resolve($agentClass, User::factory()->make(['id' => 7]))->model;
}

it('routes paying members to the paid model', function (): void {
    expect(modelResolvedFor(new TierEntitlement(SubscriptionTier::Starter), NutritionSpecialist::class))->toBe('gpt-6.1-sol');
});

it('keeps everyone else on the standard model', function (TierEntitlement $entitlement, string $agentClass, string $model): void {
    expect(modelResolvedFor($entitlement, $agentClass))->toBe($model);
})->with([
    'free member' => [TierEntitlement::free(), NutritionSpecialist::class, 'gemini-3.6-flash'],
    'billing switched off' => [new TierEntitlement(SubscriptionTier::Snap, premiumEnforcementActive: false), NutritionSpecialist::class, 'gemini-3.6-flash'],
    'first payment still pending' => [new TierEntitlement(SubscriptionTier::Snap, paymentPending: true), NutritionSpecialist::class, 'gemini-3.6-flash'],
    'agent without a paid model' => [new TierEntitlement(SubscriptionTier::Snap), ConversationTitleGeneratorAgent::class, 'gpt-5-nano'],
]);

it('uses the standard model when there is no member', function (): void {
    expect(resolve(ResolvesAgentModel::class)->resolve(NutritionSpecialist::class)->model)->toBe('gemini-3.6-flash');
});

it('keeps paying members on the standard model and reports it when the paid model is misconfigured', function (): void {
    Exceptions::fake();
    config()->set('ai.agent_profiles.'.NutritionSpecialist::class.'.paid', 'unregistered-model');

    expect(modelResolvedFor(new TierEntitlement(SubscriptionTier::Starter), NutritionSpecialist::class))->toBe('gemini-3.6-flash');
    Exceptions::assertReported(InvalidArgumentException::class);
});
