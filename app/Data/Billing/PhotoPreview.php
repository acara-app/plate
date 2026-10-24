<?php

declare(strict_types=1);

namespace App\Data\Billing;

use Spatie\LaravelData\Data;

final class PhotoPreview extends Data
{
    public function __construct(
        public string $status,
        public ?string $startsAt = null,
        public ?string $expiresAt = null,
        public ?string $nextScanAt = null,
    ) {}
}
