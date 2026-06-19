<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class NutrientValues extends Data
{
    public function __construct(
        public float $calories,
        public float $protein,
        public float $carbs,
        public float $fat,
    ) {}

    public function atwaterDeviationRatio(): ?float
    {
        if ($this->calories <= 0.0) {
            return null; // @codeCoverageIgnore
        }

        $atwater = 4 * $this->protein + 4 * $this->carbs + 9 * $this->fat;

        return round(abs($this->calories - $atwater) / $this->calories, 4);
    }
}
