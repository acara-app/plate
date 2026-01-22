<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

interface ReflectOnMemoriesTool
{
    /**
     * Analyze recent memories to generate high-level insights or pattern recognition.
     *
     * @param  int  $lookbackWindow  How many recent memories to analyze.
     * @return array<string> List of new insights generated from reflection.
     */
    public function __invoke(int $lookbackWindow = 50): array;
}
