<?php

declare(strict_types=1);

namespace App\Ai\Exceptions\Memory;

use Exception;

/**
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

    public static function emptyFilter(): self
    {
        return new self(
            message: 'A non-empty filter is required for this operation.',
        );
    }

    /**
     * @param  array<string>  $allowedFields
     */
    public static function invalidField(string $field, array $allowedFields): self
    {
        $allowed = implode(', ', $allowedFields);

        return new self(
            message: sprintf("Invalid filter field '%s'. Allowed fields: %s", $field, $allowed),
            field: $field,
        );
    }

    public static function invalidValue(string $field, mixed $value, string $expectedType): self
    {
        $actualType = get_debug_type($value);

        return new self(
            message: sprintf("Invalid value for filter field '%s'. Expected %s, got %s.", $field, $expectedType, $actualType),
            field: $field,
        );
    }
}
