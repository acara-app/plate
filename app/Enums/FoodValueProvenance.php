<?php

declare(strict_types=1);

namespace App\Enums;

enum FoodValueProvenance: string
{
    case Reference = 'reference';
    case Model = 'model';
}
