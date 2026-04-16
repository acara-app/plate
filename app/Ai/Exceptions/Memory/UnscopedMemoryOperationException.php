<?php

declare(strict_types=1);

namespace App\Ai\Exceptions\Memory;

use RuntimeException;

final class UnscopedMemoryOperationException extends RuntimeException
{
    public static function forDelete(): self
    {
        return new self(
            'Memory delete requires a user scope. Provide filter[\'user_id\'] or run inside an authenticated context.',
        );
    }
}
