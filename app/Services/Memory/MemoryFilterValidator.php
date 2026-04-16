<?php

declare(strict_types=1);

namespace App\Services\Memory;

use App\Ai\Exceptions\Memory\InvalidMemoryFilterException;

final readonly class MemoryFilterValidator
{
    /**
     * @var array<int, string>
     */
    private const array ALLOWED_FIELDS = [
        'category',
        'categories',
        'source',
        'importance_min',
        'importance_max',
        'user_id',
        'is_archived',
        'tags',
    ];

    /**
     * @param  array<string, mixed>  $filter
     *
     * @throws InvalidMemoryFilterException
     */
    public function validate(array $filter): void
    {
        foreach ($filter as $field => $value) {
            if (! in_array($field, self::ALLOWED_FIELDS, true)) {
                throw InvalidMemoryFilterException::invalidField($field, self::ALLOWED_FIELDS);
            }

            $this->validateFieldType($field, $value);
        }
    }

    /**
     * @param  array<string, mixed>  $filter
     *
     * @throws InvalidMemoryFilterException
     */
    public function requireNonEmpty(array $filter): void
    {
        if ($filter === []) {
            throw InvalidMemoryFilterException::emptyFilter();
        }

        $this->validate($filter);
    }

    private function validateFieldType(string $field, mixed $value): void
    {
        $valid = match ($field) {
            'category', 'source' => is_string($value),
            'categories', 'tags' => is_array($value),
            'importance_min', 'importance_max', 'user_id' => is_int($value) || (is_string($value) && ctype_digit($value)),
            'is_archived' => is_bool($value) || in_array($value, [0, 1, '0', '1'], true),
            default => true,
        };

        if (! $valid) {
            $expected = match ($field) {
                'category', 'source' => 'string',
                'categories', 'tags' => 'array',
                'importance_min', 'importance_max', 'user_id' => 'integer',
                'is_archived' => 'boolean',
                default => 'unknown',
            };

            throw InvalidMemoryFilterException::invalidValue($field, $value, $expected);
        }
    }
}
