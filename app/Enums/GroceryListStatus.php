<?php

declare(strict_types=1);

namespace App\Enums;

enum GroceryListStatus: string
{
    case Generating = 'generating';
    case Active = 'active';
    case Completed = 'completed';
    case Failed = 'failed';
}
