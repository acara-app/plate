<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Enums\AiAllowanceReset;
use App\Enums\SubscriptionTier;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Data;

final class AiAllowance extends Data
{
    #[Computed]
    public int $percentage;

    #[Computed]
    public bool $exhausted;

    #[Computed]
    public bool $canUpgrade;

    public function __construct(
        public SubscriptionTier $tier,
        public AiAllowanceReset $resets,
        #[Hidden]
        public float $used,
        #[Hidden]
        public float $limit,
        public CarbonImmutable $resetsAt,
    ) {
        $this->percentage = $this->limit > 0 ? (int) min(100, floor($this->used / $this->limit * 100)) : 100;
        $this->exhausted = $this->used >= $this->limit;
        $this->canUpgrade = in_array($this->tier, [SubscriptionTier::Free, SubscriptionTier::Starter], true);
    }

    public function allows(float $estimatedCost): bool
    {
        return $this->used + $estimatedCost <= $this->limit;
    }
}
