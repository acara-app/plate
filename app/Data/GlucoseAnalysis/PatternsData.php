<?php

declare(strict_types=1);

namespace App\Data\GlucoseAnalysis;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class PatternsData extends Data
{
    public function __construct(
        public bool $consistentlyHigh,
        public bool $consistentlyLow,
        public bool $highVariability,
        public bool $postMealSpikes,
        public string $hypoglycemiaRisk,
        public string $hyperglycemiaRisk,
    ) {}
}
