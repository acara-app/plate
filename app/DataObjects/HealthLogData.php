<?php

declare(strict_types=1);

namespace App\DataObjects;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class HealthLogData extends Data
{
    public function __construct(
        public bool $isHealthData,
        public string $logType,
        public ?float $glucoseValue = null,
        public ?string $glucoseReadingType = null,
        public ?string $glucoseUnit = null,
        public ?int $carbsGrams = null,
        public ?float $insulinUnits = null,
        public ?string $insulinType = null,
        public ?string $medicationName = null,
        public ?string $medicationDosage = null,
        public ?float $weight = null,
        public ?int $bpSystolic = null,
        public ?int $bpDiastolic = null,
        public ?string $exerciseType = null,
        public ?int $exerciseDurationMinutes = null,
        public ?CarbonInterface $measuredAt = null,
    ) {}
}
