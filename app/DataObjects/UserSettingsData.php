<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class UserSettingsData extends Data
{
    public function __construct(
        public bool $glucoseNotificationsEnabled = true,
        public ?int $glucoseNotificationLowThreshold = null,
        public ?int $glucoseNotificationHighThreshold = null,
    ) {}

    /**
     * Get the effective low threshold, using the user's setting or the global default.
     */
    public function effectiveLowThreshold(): int
    {
        if ($this->glucoseNotificationLowThreshold !== null) {
            return $this->glucoseNotificationLowThreshold;
        }

        $default = config('glucose.hypoglycemia_threshold');

        return is_int($default) ? $default : 70;
    }

    /**
     * Get the effective high threshold - user's value or global default
     */
    public function effectiveHighThreshold(): int
    {
        if ($this->glucoseNotificationHighThreshold !== null) {
            return $this->glucoseNotificationHighThreshold;
        }

        $default = config('glucose.hyperglycemia_threshold');

        return is_int($default) ? $default : 140;
    }
}
