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
        public ?string $fallbackProvider = null,
        public ?string $fallbackModel = null,
    ) {}

    public static function standard(): self
    {
        $fallbackProvider = config('ai.photo_analysis.fallback.provider');
        $fallbackModel = config('ai.photo_analysis.fallback.model');

        return new self(
            config()->string('ai.photo_analysis.provider'),
            config()->string('ai.photo_analysis.model'),
            fallbackProvider: is_string($fallbackProvider) ? $fallbackProvider : null,
            fallbackModel: is_string($fallbackModel) ? $fallbackModel : null,
        );
    }

    /**
     * @return array<string, string>
     */
    public function providers(): array
    {
        $providers = [$this->provider => $this->model];

        if ($this->fallbackProvider !== null && $this->fallbackModel !== null) {
            $providers[$this->fallbackProvider] ??= $this->fallbackModel;
        }

        return $providers;
    }
}
