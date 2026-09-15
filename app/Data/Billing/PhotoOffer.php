<?php

declare(strict_types=1);

namespace App\Data\Billing;

use Spatie\LaravelData\Data;

final class PhotoOffer extends Data
{
    public function __construct(
        public int $productId,
        public string $name,
        public string $formattedPrice,
        public int $scans,
    ) {}
}
