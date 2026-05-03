<?php

declare(strict_types=1);

use App\Data\Billing\CreditWarning;
use App\Enums\SubscriptionTier;
use Carbon\CarbonImmutable;

covers(CreditWarning::class);

it('serializes to a snake_case array shape consumed by the chat page', function (): void {
    $warning = new CreditWarning(
        limitType: 'rolling',
        tier: SubscriptionTier::Free,
        currentCredits: 85,
        limitCredits: 100,
        percentage: 85,
        resetsAt: CarbonImmutable::parse('2026-05-03T00:00:00+00:00'),
        resetsIn: '23 hours 59 minutes',
    );

    expect($warning->toArray())->toBe([
        'limit_type' => 'rolling',
        'tier' => 'free',
        'tier_label' => 'Free',
        'current_credits' => 85,
        'limit_credits' => 100,
        'percentage' => 85,
        'resets_at' => '2026-05-03T00:00:00+00:00',
        'resets_in' => '23 hours 59 minutes',
    ]);
});
