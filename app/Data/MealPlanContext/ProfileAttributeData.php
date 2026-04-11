<?php

declare(strict_types=1);

namespace App\Data\MealPlanContext;

use App\Enums\AllergySeverity;
use App\Enums\UserProfileAttributeCategory;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** @codeCoverageIgnore */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(CamelCaseMapper::class)]
final class ProfileAttributeData extends Data
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $value,
        public ?UserProfileAttributeCategory $category,
        public ?AllergySeverity $severity,
        public ?string $notes,
        public ?array $metadata,
    ) {}
}
