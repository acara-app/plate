<?php

declare(strict_types=1);

namespace App\Ai\Exceptions\Memory;

use Exception;

/**
 * Thrown when a requested memory cannot be found.
 */
final class MemoryNotFoundException extends Exception
{
    public function __construct(
        public readonly string $memoryId,
        string $message = '',
    ) {
        parent::__construct(
            $message ?: "Memory with ID '{$memoryId}' was not found."
        );
    }
}
