<?php

declare(strict_types=1);

namespace App\Data\MobileSync;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;

#[MapInputName(CamelCaseMapper::class)]
final class BloodGlucoseMetadata extends Data
{
    public function __construct(
        public string $glucoseReadingType = 'random',
    ) {}

    /**
     * @param  array<string, mixed>|null  $raw
     * @return array<string, mixed>
     */
    public static function normalize(?array $raw): array
    {
        /** @var array<string, mixed> $result */
        $result = self::from($raw ?? [])->toArray();

        return $result;
    }
}
