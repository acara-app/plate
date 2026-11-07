<?php

declare(strict_types=1);

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Actions\Billing\ResolveAiAllowance;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Enums\AiAllowanceReset;
use App\Enums\SubscriptionTier;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Models\AiUsage;
use App\Models\User;
use App\Services\Ai\ConfiguredAgentModels;
use Carbon\CarbonImmutable;
use Laravel\Cashier\Subscription;

covers(EnforceAiUsageLimit::class, ResolveAiAllowance::class);

beforeEach(function (): void {
    sellPhotoPlans();
});

it("stops a free account once this month's free AI is used up", function (): void {
    $user = User::factory()->create();
    AiUsage::factory()->create(['user_id' => $user->id, 'cost' => 0.50]);

    expect(function () use ($user): void {
        resolve(EnforceAiUsageLimit::class)->handle($user);
    })->toThrow(UsageLimitExceededException::class);
});

it('gives a Starter member a daily allowance that resets at midnight', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-07 15:00:00', 'UTC'));
    $user = User::factory()->create();
    Subscription::factory()->for($user)->active()->withPrice('price_starter')->create();
    AiUsage::factory()->create(['user_id' => $user->id, 'cost' => 0.50, 'created_at' => now()->subDay()]);

    $allowance = resolve(ResolveAiAllowance::class)->handle($user);

    expect($allowance?->tier)->toBe(SubscriptionTier::Starter)
        ->and($allowance?->resets)->toBe(AiAllowanceReset::Daily)
        ->and($allowance?->percentage)->toBe(0)
        ->and($allowance?->resetsAt->toIso8601String())->toBe('2026-11-08T00:00:00+00:00');

    resolve(EnforceAiUsageLimit::class)->handle($user, resolve(ConfiguredAgentModels::class)->forAgent(FoodPhotoAnalyzerAgent::class));

    AiUsage::factory()->create(['user_id' => $user->id, 'cost' => 0.50]);

    expect(function () use ($user): void {
        resolve(EnforceAiUsageLimit::class)->handle($user);
    })->toThrow(UsageLimitExceededException::class);
});

it('never limits a self-hosted install without billing', function (): void {
    disableBilling();
    $user = User::factory()->create();
    AiUsage::factory()->create(['user_id' => $user->id, 'cost' => 100]);

    resolve(EnforceAiUsageLimit::class)->handle($user);

    expect(resolve(ResolveAiAllowance::class)->handle($user))->toBeNull();
});
