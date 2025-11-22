<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\Enums\MealPlanType;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class MealPlanData extends Data
{
    /**
     * @param  DataCollection<int, MealData>  $meals
     * @param  array{protein: int, carbs: int, fat: int}|null  $macronutrientRatios
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public MealPlanType $type,
        public ?string $name,
        public ?string $description,
        public int $durationDays,
        public ?float $targetDailyCalories,
        public ?array $macronutrientRatios,
        #[DataCollectionOf(MealData::class)]
        public DataCollection $meals = new DataCollection(MealData::class, []),
        public ?array $metadata = null,
    ) {}
}
