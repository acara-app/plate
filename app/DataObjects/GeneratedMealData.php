<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class GeneratedMealData extends Data
{
    /**
     * @param  array<int, string>|null  $ingredients
     * @param  array<int, string>|null  $instructions
     * @param  array<int, string>|null  $dietaryTags
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public string $mealType,
        public ?string $cuisine,
        public float $calories,
        public float $proteinGrams,
        public float $carbsGrams,
        public float $fatGrams,
        public ?float $fiberGrams = null,
        public ?array $ingredients = null,
        public ?array $instructions = null,
        public ?int $prepTimeMinutes = null,
        public ?int $cookTimeMinutes = null,
        public int $servings = 1,
        public ?array $dietaryTags = null,
        public ?string $glycemicIndexEstimate = null,
        public ?string $glucoseImpactNotes = null,
    ) {}
}
