<?php

declare(strict_types=1);

namespace App\Enums;

enum StressLevel: int
{
    case Calm = 1;
    case Moderate = 2;
    case High = 3;
}
