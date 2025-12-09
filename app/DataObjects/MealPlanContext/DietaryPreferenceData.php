<?php

declare(strict_types=1);

namespace App\DataObjects\MealPlanContext;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(CamelCaseMapper::class)]
final class DietaryPreferenceData extends Data
{
    public function __construct(
        public string $name,
        public ?string $type,
        public ?string $description,
    ) {}
}
