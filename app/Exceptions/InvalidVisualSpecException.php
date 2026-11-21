<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class InvalidVisualSpecException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct("The visual was rejected: {$reason}.");
    }
}
