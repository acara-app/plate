<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

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
