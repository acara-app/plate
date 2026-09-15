<?php

declare(strict_types=1);

namespace App\Data\Billing;

use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;

final class PhotoEntitlement extends Data
{
    #[Computed]
    public ?int $remaining;

    #[Computed]
    public bool $exhausted;

    public function __construct(
        public bool $enabled = false,
        public ?int $limit = null,
        public int $used = 0,
        public ?string $resetsAt = null,
        public string $mode = 'standard',
        public bool $canUpgrade = false,
        public ?PhotoOffer $offer = null,
    ) {
        $this->remaining = $this->limit === null ? null : max(0, $this->limit - $this->used);
        $this->exhausted = $this->limit !== null && $this->used >= $this->limit;
    }

    public function remaining(): ?int
    {
        return $this->remaining;
    }

    public function exhausted(): bool
    {
        return $this->exhausted;
    }
}
