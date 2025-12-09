<?php

declare(strict_types=1);

namespace App\DataObjects\MealPlanContext;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;

#[MapOutputName(CamelCaseMapper::class)]
final class HealthConditionData extends Data
{
    /**
     * @param  array<string>|null  $recommendedNutrients
     * @param  array<string>|null  $nutrientsToLimit
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $nutritionalImpact,
        public ?array $recommendedNutrients,
        public ?array $nutrientsToLimit,
        public ?string $notes,
    ) {}
}
