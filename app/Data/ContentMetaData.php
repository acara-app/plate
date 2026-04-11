<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class ContentMetaData extends Data
{
    /**
     * @param  array<int, array{slug: string, anchor: string}>|null  $manualLinks
     */
    public function __construct(
        public string $seoTitle,
        public string $seoDescription,
        public ?array $manualLinks = [],
    ) {}
}
