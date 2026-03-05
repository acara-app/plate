<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class AttributeMetadataData extends Data
{
    /**
     * @param  array<string>|null  $dietaryRules
     * @param  array<string>|null  $foodsToAvoid
     * @param  array<string>|null  $foodsToPrioritize
     * @param  array<string>|null  $hiddenSources
     * @param  array<string>|null  $requirements
     */
    public function __construct(
        public ?string $safetyLevel,
        public ?array $dietaryRules,
        public ?array $foodsToAvoid,
        public ?array $foodsToPrioritize,
        public ?int $carbLimitPerMealG,
        public ?int $minFibrePerMealG,
        public ?array $hiddenSources,
        public ?array $requirements,
        public ?string $generalAdvice,
    ) {}
}
