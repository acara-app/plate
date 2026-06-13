<?php

declare(strict_types=1);

namespace App\Enums\Benchmark;

enum CameraAngle: string
{
    case TopDown = 'top-down';
    case Angled = 'angled';
    case Side = 'side';
}
