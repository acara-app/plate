<?php

declare(strict_types=1);

namespace App\Enums;

enum AnimalProductChoice: string
{
    case Omnivore = 'omnivore';
    case Pescatarian = 'pescatarian';
    case Vegan = 'vegan';

    public function label(): string
    {
        return match ($this) {
            self::Omnivore => 'I love meat/fish.',
            self::Pescatarian => 'I prefer plants, but eat fish/eggs.',
            self::Vegan => 'Strictly plants only.',
        };
    }
}
