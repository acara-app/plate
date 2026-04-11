<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Meal;
use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class MealResponseData extends Data
{
    /**
     * @param  array<int, string>|null  $ingredients
     * @param  array{protein: float, carbs: float, fat: float}  $macro_percentages
     */
    public function __construct(
        public int $id,
        public string $type,
        public string $name,
        public ?string $description,
        public ?string $preparation_instructions,
        public ?array $ingredients,
        public ?string $portion_size,
        public float $calories,
        public ?float $protein_grams,
        public ?float $carbs_grams,
        public ?float $fat_grams,
        public ?int $preparation_time_minutes,
        public array $macro_percentages,
    ) {}

    public static function fromMeal(Meal $meal): self
    {
        return new self(
            id: $meal->id,
            type: $meal->type->value,
            name: $meal->name,
            description: $meal->description,
            preparation_instructions: $meal->preparation_instructions,
            ingredients: $meal->ingredients,
            portion_size: $meal->portion_size,
            calories: (float) $meal->calories,
            protein_grams: $meal->protein_grams ? (float) $meal->protein_grams : null,
            carbs_grams: $meal->carbs_grams ? (float) $meal->carbs_grams : null,
            fat_grams: $meal->fat_grams ? (float) $meal->fat_grams : null,
            preparation_time_minutes: $meal->preparation_time_minutes,
            macro_percentages: $meal->macroPercentages(),
        );
    }
}
