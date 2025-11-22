<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class NutritionCorrectionData extends Data
{
    public function __construct(
        public float $original,
        public float $verified,
        public float $corrected,
        public float $discrepancyPercent,
    ) {}
}
