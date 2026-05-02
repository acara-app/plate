<?php

declare(strict_types=1);

use App\Enums\SubscriptionTier;
use App\Exceptions\Billing\UsageLimitExceededException;
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
        limitCredits: 9_000,
        resetsAt: now()->addDays(2),
    );

    $payload = $exception->toPayload();

    expect($payload['limit_type'])->toBe('monthly')
        ->and($payload['tier'])->toBe('plus')
        ->and($payload['tier_label'])->toBe('Plus')
        ->and($payload['current_credits'])->toBe(9_500)
        ->and($payload['limit_credits'])->toBe(9_000);
});
