<?php

declare(strict_types=1);

namespace App\Enums;

enum PreferredLanguage: string
{
    case English = 'en';
    case French = 'fr';
    case Mongolian = 'mn';

    /**
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        return [
            self::English->value => self::English->label(),
            self::French->value => self::French->label(),
            self::Mongolian->value => self::Mongolian->label(),
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::French => 'Français',
            self::Mongolian => 'Монгол',
        };
    }
}
