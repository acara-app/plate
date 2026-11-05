<?php

declare(strict_types=1);

namespace App\Enums;

enum MeasurementAssessment: string
{
    case Plausible = 'plausible';
    case LooksLikePounds = 'looks_like_pounds';
    case LooksLikeInches = 'looks_like_inches';
    case Implausible = 'implausible';
}
