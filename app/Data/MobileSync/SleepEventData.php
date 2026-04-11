<?php

declare(strict_types=1);

namespace App\Data\MobileSync;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class SleepEventData extends Data
{
    public function __construct(
        public string $type,
        public string $stage,
        public string $started_at,
        public string $ended_at,
        public ?string $source = null,
        public ?string $sample_uuid = null,
    ) {}
}
