<?php

declare(strict_types=1);

namespace App\Data\CaffeineGuidance;

use Spatie\LaravelData\Data;

/**
 * @codeCoverageIgnore
 */
final class TimingCardData extends Data
{
    public function __construct(
        public string $title,
        public string $body,
    ) {}
}
