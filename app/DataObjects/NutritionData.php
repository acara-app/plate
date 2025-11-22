<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class NutritionData extends Data
{
    public function __construct(
        public ?float $calories,
        public ?float $protein,
        public ?float $carbs,
        public ?float $fat,
        public ?float $fiber,
        public ?float $sugar,
        public ?float $sodium,
    ) {}
}
