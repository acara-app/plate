<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class MediterraneanDietFoodData extends Data
{
    public function __construct(
        public string $name,
        public int $calories,
        public int|float $protein,
        public int|float $fat,
        public int|float $saturatedFat,
        public int|float $fiber,
    ) {}
}
