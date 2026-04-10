<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class HealthUnitConversionException extends RuntimeException
{
    public function __construct(
        public readonly string $typeIdentifier,
        public readonly string $fromUnit,
        public readonly string $canonicalUnit,
    ) {
        parent::__construct(sprintf(
            'No unit conversion defined for %s from "%s" to canonical "%s".',
            $typeIdentifier,
            $fromUnit,
            $canonicalUnit,
        ));
    }
}
