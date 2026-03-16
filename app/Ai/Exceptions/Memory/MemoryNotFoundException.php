<?php

declare(strict_types=1);

namespace App\Ai\Exceptions\Memory;

use Exception;

final class MemoryNotFoundException extends Exception
{
    public function __construct(
        public readonly string $memoryId,
        string $message = '',
    ) {
        parent::__construct(
            $message ?: sprintf("Memory with ID '%s' was not found.", $memoryId)
        );
    }
}
