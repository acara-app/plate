<?php

declare(strict_types=1);

namespace App\Ai\Exceptions\Memory;

use Exception;

/**
 * Thrown when an invalid filter is provided for memory operations.
 *
 * @property-read array<string, mixed> $filter
 * @property-read string|null $field
 */
final class InvalidMemoryFilterException extends Exception
{
    /**
     * @param  array<string, mixed>  $filter
     */
    public function __construct(
        string $message,
        public readonly array $filter = [],
        public readonly ?string $field = null,
    ) {
        parent::__construct($message);
    }

    /**
     * Create exception for empty filter when one is required.
     */
    public static function emptyFilter(): self
    {
        return new self(
            message: 'A non-empty filter is required for this operation.',
        );
    }

    /**
     * Create exception for invalid filter field.
     *
     * @param  array<string>  $allowedFields
     */
    public static function invalidField(string $field, array $allowedFields): self
    {
        $allowed = implode(', ', $allowedFields);

        return new self(
            message: "Invalid filter field '{$field}'. Allowed fields: {$allowed}",
            field: $field,
        );
    }

    /**
     * Create exception for invalid filter value.
     */
    public static function invalidValue(string $field, mixed $value, string $expectedType): self
    {
        $actualType = get_debug_type($value);

        return new self(
            message: "Invalid value for filter field '{$field}'. Expected {$expectedType}, got {$actualType}.",
            field: $field,
        );
    }
}
