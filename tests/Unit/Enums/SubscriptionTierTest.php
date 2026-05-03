<?php

declare(strict_types=1);

use App\Enums\SubscriptionTier;

covers(SubscriptionTier::class);

it('maps product names case-insensitively to tier cases', function (string $name, SubscriptionTier $expected): void {
    expect(SubscriptionTier::fromProductName($name))->toBe($expected);
})->with([
    'lowercase free' => ['free', SubscriptionTier::Free],
    'titlecase Free' => ['Free', SubscriptionTier::Free],
    'lowercase basic' => ['basic', SubscriptionTier::Basic],
    'public Supporter name' => ['Supporter', SubscriptionTier::Basic],
    'lowercase plus' => ['plus', SubscriptionTier::Plus],
    'public Pro name' => ['Pro', SubscriptionTier::Plus],
]);

it('uses customer-facing tier labels for the Cloud pricing catalog', function (): void {
    expect(SubscriptionTier::Free->label())->toBe('Free')
        ->and(SubscriptionTier::Basic->label())->toBe('Supporter')
        ->and(SubscriptionTier::Plus->label())->toBe('Pro');
});
