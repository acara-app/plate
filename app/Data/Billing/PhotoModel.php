<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use Spatie\LaravelData\Data;

final class PhotoModel extends Data
{
    /** @param array<string, mixed> $options */
    public function __construct(
        public string $provider,
        public string $model,
        public int $maxTokens = 35000,
        public array $options = [],
    ) {}

    public static function standard(): self
    {
        return new self('gemini', FoodPhotoAnalyzerAgent::pinnedModel());
    }
}
