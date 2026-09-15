<?php

declare(strict_types=1);

namespace App\Data\Billing;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

final class AiBudget extends Data
{
    public function __construct(
        public float $used,
        public float $limit,
        public CarbonImmutable $resetsAt,
    ) {}
}
