<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

interface ReflectOnMemoriesTool
{
    /**
     * Analyze recent memories to generate high-level insights or pattern recognition.
     *
     * This tool performs meta-cognitive analysis on stored memories to identify:
     * - Recurring themes or patterns
     * - Contradictions or inconsistencies
     * - Opportunities for consolidation
     * - User behavior patterns or preferences
     *
     * @param  int  $lookbackWindow  How many recent memories to analyze.
     * @param  string|null  $context  Optional context to focus reflection (e.g., 'user preferences', 'work habits').
     * @param  array<string>  $categories  Only reflect on memories in these categories (empty = all).
     * @return array<string> List of new insights generated from reflection.
     */
    public function __invoke(
        int $lookbackWindow = 50,
        ?string $context = null,
        array $categories = [],
    ): array;
}
