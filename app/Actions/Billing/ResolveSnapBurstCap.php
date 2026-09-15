<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Contracts\Billing\ResolvesUserTier;
use App\Models\User;

final readonly class ResolveSnapBurstCap
{
    public const string LIMITER = 'snap-to-track-analyze';

    public function __construct(private ResolvesUserTier $tiers) {}

    public static function keyFor(?User $user, ?string $ip): string
    {
        return self::LIMITER.':'.($user instanceof User ? $user->id : (string) $ip);
    }

    public static function cacheKeyFor(?User $user, ?string $ip): string
    {
        return md5(self::LIMITER.self::keyFor($user, $ip));
    }

    public function handle(?User $user): int
    {
        $default = config()->integer('plate.snap_to_track.burst_caps.default', 5);

        if (! $user instanceof User) {
            return $default;
        }

        $entitlement = $this->tiers->resolve($user);

        if ($entitlement->isUnrestricted()) {
            return $default;
        }

        return config()->integer(
            'plate.snap_to_track.burst_caps.'.$entitlement->tier->value,
            $default,
        );
    }
}
