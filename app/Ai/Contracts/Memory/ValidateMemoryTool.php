<?php

declare(strict_types=1);

namespace App\Ai\Contracts\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\DataObjects\Memory\MemoryValidationResultData;

interface ValidateMemoryTool
{
    /**
     * Check if a memory's content is still valid/current.
     *
     * Uses AI to evaluate whether a memory contains accurate, up-to-date
     * information. Particularly useful for fact-based memories that may
     * become outdated over time.
     *
     * @param  string  $memoryId  The memory to validate.
     * @param  string|null  $context  Additional context to help validation.
     * @return MemoryValidationResultData Validation result with confidence and reasoning.
     *
     * @throws MemoryNotFoundException When the memory ID does not exist.
     */
    public function __invoke(string $memoryId, ?string $context = null): MemoryValidationResultData;
}
