<?php

declare(strict_types=1);

namespace App\Ai\Contracts;

interface SystemPromptProvider
{
    public function run(): string;
}
