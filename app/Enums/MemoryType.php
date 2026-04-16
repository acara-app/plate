<?php

declare(strict_types=1);

namespace App\Enums;

enum MemoryType: string
{
    case Fact = 'fact';
    case Preference = 'preference';
    case Goal = 'goal';
    case Event = 'event';
    case Skill = 'skill';
    case Relationship = 'relationship';
    case Habit = 'habit';
    case Context = 'context';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
