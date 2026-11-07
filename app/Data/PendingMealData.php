<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class PendingMealData extends Data
{
    public function __construct(
        public bool $expired,
        public ?string $reviewUrl = null,
    ) {}
}
