<?php

declare(strict_types=1);

namespace App\Utilities;

final class LanguageUtil
{
    /**
     * @var array<string, string>
     */
    private const array OPTIONS = [
        'en' => 'English',
        'fr' => 'Français',
        'mn' => 'Монгол',
    ];

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::OPTIONS;
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function get(string $code): ?string
    {
        return self::all()[$code] ?? null;
    }

    public static function has(string $code): bool
    {
        return isset(self::all()[$code]);
    }

    public static function default(): string
    {
        return 'en';
    }
}
