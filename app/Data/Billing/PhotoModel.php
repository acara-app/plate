<?php

declare(strict_types=1);

namespace App\Data\Billing;

use InvalidArgumentException;
use Spatie\LaravelData\Data;

final class PhotoModel extends Data
{
    public function __construct(
        public string $provider,
        public string $model,
        public int $maxTokens = 35000,
        public ?string $failoverProvider = null,
        public ?string $failoverModel = null,
    ) {}

    public static function standard(): self
    {
        $failoverProvider = config('ai.photo_analysis.failover.provider');
        $failoverModel = config('ai.photo_analysis.failover.model');

        return new self(
            config()->string('ai.photo_analysis.provider'),
            config()->string('ai.photo_analysis.model'),
            failoverProvider: is_string($failoverProvider) ? $failoverProvider : null,
            failoverModel: is_string($failoverModel) ? $failoverModel : null,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function providers(): array
    {
        $failoverProvider = $this->failoverProvider;

        if ($failoverProvider === null) {
            return [$this->provider => $this->model];
        }

        throw_if($failoverProvider === $this->provider, InvalidArgumentException::class, "The photo analysis failover must use a different provider than [{$this->provider}].");

        return [$this->provider => $this->model, $failoverProvider => $this->failoverModel];
    }
}
