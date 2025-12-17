<?php

declare(strict_types=1);

namespace App\Ai\Contracts;

use App\Enums\ModelName;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Tool;

interface AgentInterface
{
    public function modelName(): ModelName;

    public function provider(): Provider;

    public function model(): string;

    public function systemPrompt(): string;

    /**
     * @return array<int, Tool>
     */
    public function tools(): array;

    public function maxTokens(): int;

    /**
     * @return array<string, mixed>
     */
    public function clientOptions(): array;

    public function text(): PendingRequest;
}
