<?php

declare(strict_types=1);

namespace App\DataObjects\MobileSync;

use Spatie\LaravelData\Data;

final class HealthEntryData extends Data
{
    public function __construct(
        public string $type,
        public float $value,
        public string $unit,
        public string $date,
        public ?string $source = null,
        /** @var array<string, string>|null */
        public ?array $metadata = null,
        public ?string $sample_uuid = null,
        public ?string $ended_at = null,
    ) {}
}
