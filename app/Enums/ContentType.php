<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentType: string
{
    case Food = 'food';

    public function label(): string
    {
        return match ($this) {
            self::Food => 'Food',
        };
    }
}
