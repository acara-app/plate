<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * DTO for single-day meals generation response from LLM.
 * Meals do not include day_number as it will be set programmatically.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class DayMealsData extends Data
{
    /**
     * @param  DataCollection<int, SingleDayMealData>  $meals
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        #[DataCollectionOf(SingleDayMealData::class)]
        public DataCollection $meals = new DataCollection(SingleDayMealData::class, []),
        public ?array $metadata = null,
    ) {}
}
