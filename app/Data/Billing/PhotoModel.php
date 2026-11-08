<?php

declare(strict_types=1);

namespace App\Data\Billing;

use Spatie\LaravelData\Data;

final class PhotoModel extends Data
{
    public function __construct(
        public string $provider,
        public string $model,
        public int $maxTokens = 35000,
    ) {}

    public static function standard(): self
    {
        return new self(
            config()->string('ai.photo_analysis.provider'),
            config()->string('ai.photo_analysis.model'),
        );
    }
}
