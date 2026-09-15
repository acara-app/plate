<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Enums\SubscriptionTier;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;

final class BurstLimit extends Data
{
    #[Computed]
    public int $retryAfterMinutes;

    public function __construct(
        public string $tier,
        public string $tierLabel,
        public int $cap,
        public int $retryAfterSeconds,
        public ?string $resetsAt = null,
        public ?PhotoOffer $offer = null,
    ) {
        $this->retryAfterMinutes = max(1, (int) ceil($this->retryAfterSeconds / 60));
    }

    public static function forTier(
        SubscriptionTier $tier,
        int $cap,
        int $retryAfterSeconds,
        ?PhotoOffer $offer = null,
    ): self {
        return new self(
            tier: $tier->value,
            tierLabel: $tier->label(),
            cap: $cap,
            retryAfterSeconds: $retryAfterSeconds,
            resetsAt: now()->addSeconds($retryAfterSeconds)->toIso8601String(),
            offer: $offer,
        );
    }
}
