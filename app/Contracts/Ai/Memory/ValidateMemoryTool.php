<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Data\Memory\MemoryValidationResultData;

interface ValidateMemoryTool
{
    /**
     * @param  string  $memoryId  The memory to validate.
     * @param  string|null  $context  Additional context to help validation.
     * @return MemoryValidationResultData Validation result with confidence and reasoning.
     *
     * @throws MemoryNotFoundException When the memory ID does not exist.
     */
    public function execute(string $memoryId, ?string $context = null): MemoryValidationResultData;
}
