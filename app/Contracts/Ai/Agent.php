<?php

declare(strict_types=1);

namespace App\Contracts\Ai;

interface Agent
{
    public function instructions(): string;

    public function maxTokens(): int;

    /**
     * @return array<string, mixed>
     */
    public function clientOptions(): array;
}
