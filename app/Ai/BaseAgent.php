<?php

declare(strict_types=1);

namespace App\Ai;

use App\Ai\Contracts\AgentInterface;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Tool;

abstract class BaseAgent implements AgentInterface
{
    abstract public function provider(): Provider;

    abstract public function model(): string;

    abstract public function systemPrompt(): string;

    /**
     * @return array<int, Tool>
     */
    public function tools(): array
    {
        return [];
    }

    public function maxTokens(): int
    {
        return 8000;
    }

    /**
     * @return array<string, mixed>
     */
    public function clientOptions(): array
    {
        return [];
    }

    public function text(): PendingRequest
    {
        $request = Prism::text()
            ->using($this->provider(), $this->model())
            ->withSystemPrompt($this->systemPrompt())
            ->withMaxTokens($this->maxTokens());

        if ($this->tools() !== []) {
            $request->withTools($this->tools());
        }

        if ($this->clientOptions() !== []) {
            $request->withClientOptions($this->clientOptions());
        }

        return $request;
    }
}
