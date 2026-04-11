<?php

declare(strict_types=1);

namespace App\Data\MobileSync;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;

#[MapInputName(CamelCaseMapper::class)]
final class MedicationMetadata extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $displayText = null,
        public ?string $form = null,
        public ?string $hasSchedule = null,
        public ?string $isArchived = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $raw
     * @return array<string, mixed>|null
     */
    public static function normalize(?array $raw): ?array
    {
        if ($raw === null || $raw === []) {
            return null;
        }

        /** @var array<string, mixed> $result */
        $result = array_filter(
            self::from($raw)->toArray(),
            fn (mixed $value): bool => $value !== null,
        );

        return $result !== [] ? $result : null;
    }
}
