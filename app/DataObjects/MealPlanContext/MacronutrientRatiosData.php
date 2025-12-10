<?php

declare(strict_types=1);

namespace App\DataObjects\MealPlanContext;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;

#[MapOutputName(CamelCaseMapper::class)]
final class MacronutrientRatiosData extends Data
{
    public function __construct(
        public int $protein,
        public int $carbs,
        public int $fat,
    ) {}
}
