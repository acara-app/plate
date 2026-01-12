<?php

declare(strict_types=1);

namespace App\DataObjects\MealPlanContext;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(CamelCaseMapper::class)]
final class MedicationData extends Data
{
    public function __construct(
        public string $name,
        public ?string $dosage,
        public ?string $frequency,
        public ?string $purpose,
        public ?CarbonInterface $startedAt,
    ) {}
}
