<?php

declare(strict_types=1);

namespace App\Data;

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

    public function effectiveLowThreshold(): int
    {
        return $this->glucoseNotificationLowThreshold
            ?? config()->integer('glucose.hypoglycemia_threshold', 70);
    }

    public function effectiveHighThreshold(): int
    {
        return $this->glucoseNotificationHighThreshold
            ?? config()->integer('glucose.hyperglycemia_threshold', 140);
    }
}
