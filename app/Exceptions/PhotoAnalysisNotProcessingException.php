<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class PhotoAnalysisNotProcessingException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This photo analysis is no longer processing.');
    }
}
