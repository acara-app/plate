<?php

declare(strict_types=1);

namespace App\Enums\Benchmark;

enum Lighting: string
{
    case Bright = 'bright';
    case Indoor = 'indoor';
    case Dim = 'dim';
}
