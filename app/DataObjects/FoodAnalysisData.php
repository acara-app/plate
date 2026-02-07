<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
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
        public float $confidence,
    ) {}
}
