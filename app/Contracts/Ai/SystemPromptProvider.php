<?php

declare(strict_types=1);

namespace App\Contracts\Ai;

interface SystemPromptProvider
{
    public function run(): string;
}
