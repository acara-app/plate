<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class GeminiUploadedFileData extends Data
{
    public function __construct(
        public string $name,
        public string $displayName,
        public string $mimeType,
        public int $sizeBytes,
        public string $uri,
    ) {}
}
