<?php

declare(strict_types=1);

namespace App\Ai\Exceptions\Memory;

use Exception;

/**
 * Thrown when memory storage operations fail.
 *
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
     * Create exception for failed store operation.
     *
     * @param  array<string, mixed>|null  $context
     */
    public static function storeFailed(string $reason, ?array $context = null): self
    {
        return new self(
            message: "Failed to store memory: {$reason}",
            operation: 'store',
            context: $context,
        );
    }

    /**
     * Create exception for failed update operation.
     */
    public static function updateFailed(string $memoryId, string $reason): self
    {
        return new self(
            message: "Failed to update memory '{$memoryId}': {$reason}",
            operation: 'update',
            context: ['memory_id' => $memoryId],
        );
    }

    /**
     * Create exception for failed delete operation.
     */
    public static function deleteFailed(string $reason): self
    {
        return new self(
            message: "Failed to delete memory: {$reason}",
            operation: 'delete',
        );
    }

    /**
     * Create exception for failed consolidation.
     *
     * @param  array<string>  $memoryIds
     */
    public static function consolidationFailed(array $memoryIds, string $reason): self
    {
        return new self(
            message: "Failed to consolidate memories: {$reason}",
            operation: 'consolidate',
            context: ['memory_ids' => $memoryIds],
        );
    }
}
