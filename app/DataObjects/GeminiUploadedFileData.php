<?php

declare(strict_types=1);

namespace App\DataObjects;

use Gemini\Responses\Files\MetadataResponse;
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

    public static function fromMetadataResponse(MetadataResponse $response): self
    {
        return new self(
            name: $response->name,
            displayName: $response->displayName,
            mimeType: $response->mimeType,
            sizeBytes: (int) ($response->sizeBytes ?? 0),
            uri: $response->uri,
        );
    }
}
