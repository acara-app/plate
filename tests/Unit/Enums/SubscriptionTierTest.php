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
    'lowercase plus' => ['plus', SubscriptionTier::Plus],
]);
