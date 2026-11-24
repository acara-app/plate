<?php

declare(strict_types=1);

namespace App\Data\Billing;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

final class GuestCheckoutSession extends Data
{
    public function __construct(
        public string $id,
        public string $url,
        public CarbonImmutable $expiresAt,
    ) {}
}
