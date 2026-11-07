<?php

declare(strict_types=1);

use App\Data\Billing\AiAllowance;
use App\Enums\AiAllowanceReset;
use App\Enums\SubscriptionTier;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Utilities\StaticUrl;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

covers(UsageLimitExceededException::class);

function usedUpAllowance(SubscriptionTier $tier, AiAllowanceReset $resets): UsageLimitExceededException
{
    return new UsageLimitExceededException(new AiAllowance($tier, $resets, 0.5, 0.5, CarbonImmutable::parse('2026-12-01 00:00:00')));
}

it('renders a 402 with the allowance the chat and app need to show the notice', function (): void {
    $response = usedUpAllowance(SubscriptionTier::Starter, AiAllowanceReset::Daily)->render(Request::create('/'));

    expect($response->getStatusCode())->toBe(402)
        ->and($response->getData(true))->toMatchArray([
            'error' => 'usage_limit_exceeded',
            'tier' => 'starter',
            'resets' => 'daily',
            'percentage' => 100,
            'exhausted' => true,
            'can_upgrade' => true,
            'limit_type' => 'daily',
            'tier_label' => 'Starter',
        ])->toHaveKey('resets_in')->not->toHaveKeys(['used', 'limit']);
});

it('tells the user when their AI comes back and offers plans only when they can upgrade', function (SubscriptionTier $tier, AiAllowanceReset $resets, string $message, bool $linksPlans): void {
    $text = usedUpAllowance($tier, $resets)->userMessage();

    expect($text)->toContain($message)
        ->and(str_contains($text, StaticUrl::checkoutUrl()))->toBe($linksPlans);
})->with([
    'free account' => [SubscriptionTier::Free, AiAllowanceReset::Monthly, "This month's free AI is used up. It resets on December 1.", true],
    'Snap Pro member' => [SubscriptionTier::Snap, AiAllowanceReset::Daily, "Today's AI allowance is used up. It resets at midnight.", false],
]);
