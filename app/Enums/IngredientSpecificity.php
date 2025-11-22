<?php

declare(strict_types=1);

namespace App\Enums;

enum IngredientSpecificity: string
{
    case Generic = 'generic';
    case Specific = 'specific';
}
