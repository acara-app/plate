<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

interface ReflectOnMemoriesTool
{
    /**
     * @param  int  $lookbackWindow  How many recent memories to analyze.
     * @param  string|null  $context  Optional context to focus reflection (e.g., 'user preferences', 'work habits').
     * @param  array<string>  $categories  Only reflect on memories in these categories (empty = all).
     * @return array<string> List of new insights generated from reflection.
     */
    public function execute(
        int $lookbackWindow = 50,
        ?string $context = null,
        array $categories = [],
    ): array;
}
