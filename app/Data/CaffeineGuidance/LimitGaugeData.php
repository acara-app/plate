<?php

declare(strict_types=1);

namespace App\Data\CaffeineGuidance;

use App\Enums\CaffeineTone;
use Spatie\LaravelData\Data;

/**
 * @codeCoverageIgnore
 */
final class LimitGaugeData extends Data
{
    public function __construct(
        public string $label,
        public CaffeineTone $tone,
        public string $caption,
    ) {}
}
