<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class FoodItemEstimateData extends Data
{
    public const int PORTION_MAX_LENGTH = 100;

    public function __construct(
        public float $calories,
        public float $protein,
        public float $carbs,
        public float $fat,
        public float $grams,
        public string $portion = '',
        public ?float $fiber = null,
    ) {}

    public function normalized(): self
    {
        $grams = round(max(0.0, $this->grams), 1);
        $portion = mb_substr(mb_trim($this->portion), 0, self::PORTION_MAX_LENGTH);

        return new self(
            calories: round(max(0.0, $this->calories), 1),
            protein: round(max(0.0, $this->protein), 1),
            carbs: round(max(0.0, $this->carbs), 1),
            fat: round(max(0.0, $this->fat), 1),
            grams: $grams,
            portion: $portion !== '' ? $portion : mb_rtrim(mb_rtrim(number_format($grams, 1, '.', ''), '0'), '.').' g',
            fiber: $this->fiber === null ? null : round(max(0.0, $this->fiber), 1),
        );
    }
}
