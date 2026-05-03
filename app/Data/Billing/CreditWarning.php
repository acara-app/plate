<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Enums\SubscriptionTier;
use Carbon\CarbonInterface;

final readonly class CreditWarning
{
    public function __construct(
        public string $limitType,
        public SubscriptionTier $tier,
        public int $currentCredits,
        public int $limitCredits,
        public int $percentage,
        public CarbonInterface $resetsAt,
        public string $resetsIn,
    ) {}

    /**
     * @return array{
     *     limit_type: string,
     *     tier: string,
     *     tier_label: string,
     *     current_credits: int,
     *     limit_credits: int,
     *     percentage: int,
     *     resets_at: string,
     *     resets_in: string
     * }
     */
    public function toArray(): array
    {
        return [
            'limit_type' => $this->limitType,
            'tier' => $this->tier->value,
            'tier_label' => $this->tier->label(),
            'current_credits' => $this->currentCredits,
            'limit_credits' => $this->limitCredits,
            'percentage' => $this->percentage,
            'resets_at' => $this->resetsAt->toIso8601String(),
            'resets_in' => $this->resetsIn,
        ];
    }
}
