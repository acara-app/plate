<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class UserSettings extends Data
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
        return $this->glucoseNotificationLowThreshold ?? config('glucose.hypoglycemia_threshold');
    }

    /**
     * Get the effective high threshold, using the user's setting or the global default.
     */
    public function effectiveHighThreshold(): int
    {
        return $this->glucoseNotificationHighThreshold ?? config('glucose.hyperglycemia_threshold');
    }
}
