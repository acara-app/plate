<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class GeminiFileSearchStoreData extends Data
{
    public function __construct(
        public string $name,
        public string $displayName,
        public int $activeDocumentsCount = 0,
        public int $pendingDocumentsCount = 0,
        public int $failedDocumentsCount = 0,
        public int $sizeBytes = 0,
        public string $createTime,
        public string $updateTime,
    ) {}

    public function getSizeMB(): string
    {
        return number_format($this->sizeBytes / 1024 / 1024, 2);
    }

    public function hasDocuments(): bool
    {
        return $this->activeDocumentsCount > 0 || $this->pendingDocumentsCount > 0;
    }
}
