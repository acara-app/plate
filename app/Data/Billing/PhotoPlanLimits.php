<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Enums\SubscriptionTier;
use Spatie\LaravelData\Data;

final class PhotoPlanLimits extends Data
{
    public function __construct(
        public int $scansPerHour,
        public int $rollingCredits,
        public int $rollingHours,
        public int $weeklyCredits,
        public int $weeklyDays,
    ) {}

    public static function forProductName(?string $name): ?self
    {
        $tier = SubscriptionTier::fromProductName($name);

        if (! in_array($tier, [SubscriptionTier::Starter, SubscriptionTier::Snap], true)) {
            return null;
        }

        $multiplier = config()->integer('plate.credit_multiplier', 1);
        $prefix = 'plate.tier_limits.'.$tier->value;

        return new self(
            scansPerHour: config()->integer('plate.snap_to_track.burst_caps.'.$tier->value, config()->integer('plate.snap_to_track.burst_caps.default', 5)),
            rollingCredits: (int) round(config()->float($prefix.'.rolling.limit') * $multiplier),
            rollingHours: config()->integer($prefix.'.rolling.period_hours'),
            weeklyCredits: (int) round(config()->float($prefix.'.weekly.limit') * $multiplier),
            weeklyDays: config()->integer($prefix.'.weekly.period_days'),
        );
    }
}
