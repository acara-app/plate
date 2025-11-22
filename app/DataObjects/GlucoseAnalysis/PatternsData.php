<?php

declare(strict_types=1);

namespace App\DataObjects\GlucoseAnalysis;

use Spatie\LaravelData\Data;

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
