<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class EmptyMealPlanDayException extends RuntimeException
{
    public function __construct(int $dayNumber)
    {
        parent::__construct(sprintf('No meals were generated for day %d.', $dayNumber));
    }
}
