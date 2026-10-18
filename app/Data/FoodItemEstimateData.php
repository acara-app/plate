<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class FoodItemEstimateData extends Data
{
    public function __construct(
        public float $calories,
        public float $protein,
        public float $carbs,
        public float $fat,
        public float $grams,
    ) {}

    public function rounded(): self
    {
        return new self(
            calories: round(max(0.0, $this->calories), 1),
            protein: round(max(0.0, $this->protein), 1),
            carbs: round(max(0.0, $this->carbs), 1),
            fat: round(max(0.0, $this->fat), 1),
            grams: round(max(0.0, $this->grams), 1),
        );
    }
}
