<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class FoodAnalysisData extends Data
{
    /**
     * @param  DataCollection<int, FoodItemData>  $items
     */
    public function __construct(
        #[DataCollectionOf(FoodItemData::class)]
        public DataCollection $items,
        public float $totalCalories,
        public float $totalProtein,
        public float $totalCarbs,
        public float $totalFat,
        public int $confidence,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            items: FoodItemData::collect($data['items'], DataCollection::class),
            totalCalories: $data['total_calories'],
            totalProtein: $data['total_protein'],
            totalCarbs: $data['total_carbs'],
            totalFat: $data['total_fat'],
            confidence: $data['confidence'],
        );
    }
}
