<?php

declare(strict_types=1);

namespace App\Data\Ai;

use Spatie\LaravelData\Data;

final class AgentModelData extends Data
{
    /**
     * @param  list<string>  $capabilities
     * @param  array<string, mixed>  $options
     * @param  array{input: float, output: float, reasoning: float, cache_read: float, cache_write?: float}  $pricing
     */
    public function __construct(
        public string $provider,
        public string $model,
        public int $maxOutputTokens = 65536,
        public array $capabilities = [],
        public array $options = [],
        public array $pricing = ['input' => 0.0, 'output' => 0.0, 'reasoning' => 0.0, 'cache_read' => 0.0],
    ) {}

    /** @return array<string, mixed> */
    public function toSnapshot(): array
    {
        return [
            'provider' => $this->provider, 'model' => $this->model,
            'maxOutputTokens' => $this->maxOutputTokens, 'capabilities' => $this->capabilities,
            'options' => $this->options, 'pricing' => $this->pricing,
        ];
    }

    public function supports(string $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }
}
