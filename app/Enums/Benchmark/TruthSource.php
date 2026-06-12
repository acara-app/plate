<?php

declare(strict_types=1);

namespace App\Enums\Benchmark;

enum TruthSource: string
{
    case Label = 'label';
    case Reference = 'reference';
    case Recipe = 'recipe';
}
