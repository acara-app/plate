<?php

declare(strict_types=1);

namespace App\Ai\Exceptions\Memory;

use Exception;

/**
 * @property-read string|null $operation
 * @property-read array<string, mixed>|null $context
 */
final class MemoryStorageException extends Exception
{
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        string $message,
        public readonly ?string $operation = null,
        public readonly ?array $context = null,
    ) {
        parent::__construct($message);
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    public static function storeFailed(string $reason, ?array $context = null): self
    {
        return new self(
            message: 'Failed to store memory: '.$reason,
            operation: 'store',
            context: $context,
        );
    }

    public static function updateFailed(string $memoryId, string $reason): self
    {
        return new self(
            message: sprintf("Failed to update memory '%s': %s", $memoryId, $reason),
            operation: 'update',
            context: ['memory_id' => $memoryId],
        );
    }

    public static function deleteFailed(string $reason): self
    {
        return new self(
            message: 'Failed to delete memory: '.$reason,
            operation: 'delete',
        );
    }

    /**
     * @param  array<string>  $memoryIds
     */
    public static function consolidationFailed(array $memoryIds, string $reason): self
    {
        return new self(
            message: 'Failed to consolidate memories: '.$reason,
            operation: 'consolidate',
            context: ['memory_ids' => $memoryIds],
        );
    }
}
