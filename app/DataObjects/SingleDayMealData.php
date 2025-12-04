<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\Enums\MealType;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class SingleDayMealData extends Data
{
    /**
     * @param  DataCollection<int, IngredientData>|null  $ingredients
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public MealType $type,
        public string $name,
        public ?string $description,
        public ?string $preparationInstructions,
        #[DataCollectionOf(IngredientData::class)]
        public ?DataCollection $ingredients,
        public ?string $portionSize,
        public float $calories,
        public ?float $proteinGrams,
        public ?float $carbsGrams,
        public ?float $fatGrams,
        public ?int $preparationTimeMinutes,
        public int $sortOrder,
        public ?array $metadata = null,
    ) {}

    /**
     * Convert to MealData with the given day number.
     */
    public function toMealData(int $dayNumber): MealData
    {
        return new MealData(
            dayNumber: $dayNumber,
            type: $this->type,
            name: $this->name,
            description: $this->description,
            preparationInstructions: $this->preparationInstructions,
            ingredients: $this->ingredients,
            portionSize: $this->portionSize,
            calories: $this->calories,
            proteinGrams: $this->proteinGrams,
            carbsGrams: $this->carbsGrams,
            fatGrams: $this->fatGrams,
            preparationTimeMinutes: $this->preparationTimeMinutes,
            sortOrder: $this->sortOrder,
            metadata: $this->metadata,
        );
    }
}
