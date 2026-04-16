<?php

declare(strict_types=1);

namespace App\Enums;

enum MemoryCategory: string
{
    case Personal = 'personal';
    case Professional = 'professional';
    case Hobbies = 'hobbies';
    case Health = 'health';
    case Relationships = 'relationships';
    case Preferences = 'preferences';
    case Goals = 'goals';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
