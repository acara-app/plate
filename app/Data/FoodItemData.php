<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\FoodValueProvenance;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class FoodItemData extends Data
{
    public function __construct(
        public string $name,
        public float $calories,
        public float $protein,
        public float $carbs,
        public float $fat,
        public string $portion,
        public ?float $grams = null,
        public ?string $matchName = null,
        public FoodValueProvenance $provenance = FoodValueProvenance::Model,
    ) {}
}
