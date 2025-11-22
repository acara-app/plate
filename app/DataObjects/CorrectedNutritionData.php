<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class CorrectedNutritionData extends Data
{
    /**
     * @param  array<string, NutritionCorrectionData>  $correctionsApplied
     */
    public function __construct(
        public float $calories,
        public float $protein,
        public float $carbs,
        public float $fat,
        public array $correctionsApplied,
    ) {}
}
