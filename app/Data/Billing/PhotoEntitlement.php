<?php

declare(strict_types=1);

namespace App\Data\Billing;

use Spatie\LaravelData\Data;

final class PhotoEntitlement extends Data
{
    public function __construct(
        public bool $enabled = false,
        public ?int $limit = null,
        public int $used = 0,
        public ?string $resetsAt = null,
        public string $mode = 'standard',
        public bool $canUpgrade = false,
    ) {}

    public function remaining(): ?int
    {
        return $this->limit === null ? null : max(0, $this->limit - $this->used);
    }

    public function exhausted(): bool
    {
        return $this->limit !== null && $this->used >= $this->limit;
    }
}
