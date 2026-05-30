<?php

declare(strict_types=1);

use App\Enums\SubscriptionTier;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Utilities\StaticUrl;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

covers(UsageLimitExceededException::class);

it('renders an HTTP 402 JSON response with the structured limit payload', function (): void {
    $resetsAt = now()->addHours(4);

    $exception = new UsageLimitExceededException(
        limitType: 'rolling',
        tier: SubscriptionTier::Free,
        currentCredits: 100,
        limitCredits: 100,
        resetsAt: $resetsAt,
    );

    $response = $exception->render(Request::create('/'));

    expect($response->getStatusCode())->toBe(Response::HTTP_PAYMENT_REQUIRED);

    /** @var array<string, mixed> $payload */
    $payload = json_decode((string) $response->getContent(), true);

    expect($payload['error'])->toBe('usage_limit_exceeded')
        ->and($payload['limit_type'])->toBe('rolling')
        ->and($payload['tier'])->toBe(SubscriptionTier::Free->value)
        ->and($payload['tier_label'])->toBe('Free')
        ->and($payload['current_credits'])->toBe(100)
        ->and($payload['limit_credits'])->toBe(100)
        ->and($payload['resets_at'])->toBe($resetsAt->toIso8601String())
        ->and($payload['resets_in'])->toBeString()->not->toBe('');
});

it('exposes structured fields directly on the exception for non-HTTP callers', function (): void {
    $exception = new UsageLimitExceededException(
        limitType: 'monthly',
        tier: SubscriptionTier::Plus,
        currentCredits: 9_500,
        limitCredits: 10_000,
        resetsAt: now()->addDays(2),
    );

    $payload = $exception->toPayload();

    expect($payload['limit_type'])->toBe('monthly')
        ->and($payload['tier'])->toBe('plus')
        ->and($payload['tier_label'])->toBe('Pro')
        ->and($payload['current_credits'])->toBe(9_500)
        ->and($payload['limit_credits'])->toBe(10_000);
});

it('builds a friendly daily message with an upgrade link for the free tier', function (): void {
    $exception = new UsageLimitExceededException(
        limitType: 'rolling',
        tier: SubscriptionTier::Free,
        currentCredits: 119,
        limitCredits: 100,
        resetsAt: now()->addHours(18)->addMinutes(45),
    );

    expect($exception->userMessage())
        ->toContain('daily AI credits')
        ->toContain('Free plan')
        ->toContain('18 hours 45 minutes')
        ->toContain(StaticUrl::checkoutUrl());
});

it('describes the weekly window when the weekly limit is exceeded', function (): void {
    $exception = new UsageLimitExceededException(
        limitType: 'weekly',
        tier: SubscriptionTier::Basic,
        currentCredits: 2_100,
        limitCredits: 2_000,
        resetsAt: now()->addDays(2),
    );

    expect($exception->userMessage())
        ->toContain('weekly AI credits')
        ->toContain('Supporter plan')
        ->toContain(StaticUrl::checkoutUrl());
});

it('omits the upgrade link for the top tier', function (): void {
    $exception = new UsageLimitExceededException(
        limitType: 'rolling',
        tier: SubscriptionTier::Plus,
        currentCredits: 1_100,
        limitCredits: 1_000,
        resetsAt: now()->addHours(3),
    );

    expect($exception->userMessage())
        ->toContain('reached your daily AI credit limit')
        ->toContain('Pro plan')
        ->not->toContain(StaticUrl::checkoutUrl());
});
