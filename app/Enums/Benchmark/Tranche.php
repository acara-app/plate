<?php

declare(strict_types=1);

namespace App\Enums\Benchmark;

enum Tranche: string
{
    case Hand = 'hand';
    case Public = 'public';
}
