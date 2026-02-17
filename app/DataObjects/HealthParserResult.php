<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntryType;
use App\Enums\InsulinType;
use BackedEnum;
use Illuminate\Support\Facades\Date;
use Spatie\LaravelData\Data;

final class HealthParserResult extends Data
{
    /**
     * Create a new health parser result instance.
     *
     * Properties are intentionally typed as `mixed` to provide a resilience layer against
     * inconsistent LLM outputs. The `toHealthLogData` method handles strict type
     * conversion and sanitization before the data enters the application domain.
     */
    public function __construct(
        public mixed $is_health_data = null,
        public mixed $log_type = null,
        public mixed $glucose_value = null,
        public mixed $glucose_reading_type = null,
        public mixed $glucose_unit = null,
        public mixed $carbs_grams = null,
        public mixed $insulin_units = null,
        public mixed $insulin_type = null,
        public mixed $medication_name = null,
        public mixed $medication_dosage = null,
        public mixed $weight = null,
        public mixed $bp_systolic = null,
        public mixed $bp_diastolic = null,
        public mixed $exercise_type = null,
        public mixed $exercise_duration_minutes = null,
        public mixed $measured_at = null,
    ) {}

    public function toHealthLogData(): HealthLogData
    {
        return new HealthLogData(
            isHealthData: $this->toBoolean($this->is_health_data, false),
            logType: $this->toLogType($this->log_type),
            glucoseValue: $this->toFloat($this->glucose_value),
            glucoseReadingType: $this->toEnum($this->glucose_reading_type, GlucoseReadingType::class),
            glucoseUnit: $this->toEnum($this->glucose_unit, GlucoseUnit::class),
            carbsGrams: $this->toInt($this->carbs_grams),
            insulinUnits: $this->toFloat($this->insulin_units),
            insulinType: $this->toEnum($this->insulin_type, InsulinType::class),
            medicationName: $this->toString($this->medication_name),
            medicationDosage: $this->toString($this->medication_dosage),
            weight: $this->toFloat($this->weight),
            bpSystolic: $this->toInt($this->bp_systolic),
            bpDiastolic: $this->toInt($this->bp_diastolic),
            exerciseType: $this->toString($this->exercise_type),
            exerciseDurationMinutes: $this->toInt($this->exercise_duration_minutes),
            measuredAt: $this->toDateTime($this->measured_at),
        );
    }

    private function toBoolean(mixed $value, bool $default): bool
    {
        return is_bool($value) ? $value : $default;
    }

    private function toLogType(mixed $value): HealthEntryType
    {
        $string = $this->toString($value);

        return HealthEntryType::tryFrom($string ?? '') ?? HealthEntryType::Glucose;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enumClass
     * @return T|null
     */
    private function toEnum(mixed $value, string $enumClass): ?BackedEnum
    {
        $string = $this->toString($value);

        if ($string === null) {
            return null;
        }

        return $enumClass::tryFrom($string);
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === 'null') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function toInt(mixed $value): ?int
    {
        if ($value === null || $value === 'null') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function toString(mixed $value): ?string
    {
        if (in_array($value, [null, 'null', ''], true)) {
            return null;
        }

        return is_string($value) ? $value : (is_scalar($value) ? (string) $value : null);
    }

    private function toDateTime(mixed $value): ?\Carbon\CarbonInterface
    {
        $string = $this->toString($value);

        return $string !== null ? Date::parse($string) : null;
    }
}
