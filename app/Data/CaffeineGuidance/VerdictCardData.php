<?php

declare(strict_types=1);

namespace App\Data\CaffeineGuidance;

use App\Enums\CaffeineTone;
use Spatie\LaravelData\Data;

/**
 * @codeCoverageIgnore
 */
final class VerdictCardData extends Data
{
    public function __construct(
        public string $title,
        public string $body,
        public string $badge,
        public CaffeineTone $tone,
    ) {}
}
