<?php

declare(strict_types=1);

namespace App\Ai;

use App\Contracts\Ai\Agent;
use App\Enums\ModelName;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Tool;

abstract class BaseAgent implements Agent
{
    abstract public function systemPrompt(): string;

    public function modelName(): ModelName
    {
        return ModelName::GEMINI_3_FLASH;
    }

    public function provider(): Provider
    {
        return $this->modelName()->getProvider();
    }

    public function model(): string
    {
        return $this->modelName()->value;
    }

    /**
     * @return array<int, Tool>
     */
    public function tools(): array
    {
        return [];
    }

    public function maxTokens(): int
    {
        return max(8000, $this->modelName()->getMinMaxTokens());
    }

    /**
     * @return array<string, mixed>
     */
    public function clientOptions(): array
    {
        return [];
    }

    /**
     * Get the temperature for this agent.
     */
    public function temperature(): float
    {
        return $this->modelName()->getRecommendedTemperature();
    }

    /**
     * Get provider-specific options for the model.
     *
     * @return array<string, mixed>
     */
    public function providerOptions(): array
    {
        $options = [];
        $modelName = $this->modelName();

        if ($modelName->requiresThinkingMode()) {
            $thinkingBudget = $modelName->getThinkingBudget();

            if ($thinkingBudget !== null) {
                $options['thinkingBudget'] = $thinkingBudget;
            }

            if ($this->tools() !== []) {
                $options['thoughtSignature'] = true;
            }
        }

        return $options;
    }

    public function text(): PendingRequest
    {
        $request = Prism::text()
            ->using($this->provider(), $this->model())
            ->withSystemPrompt($this->systemPrompt())
            ->withMaxTokens($this->maxTokens());

        if ($this->modelName()->supportsTemperature()) {
            $request->usingTemperature($this->temperature());
        }

        if ($this->tools() !== []) {
            $request->withTools($this->tools());
        }

        if ($this->clientOptions() !== []) {
            $request->withClientOptions($this->clientOptions());
        }

        $providerOptions = $this->providerOptions();

        if ($providerOptions !== []) {
            $request->withProviderOptions($providerOptions);
        }

        return $request;
    }
}
