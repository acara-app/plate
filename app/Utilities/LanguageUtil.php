<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Support\Facades\File;

final class LanguageUtil
{
    /**
     * @var array<string, string>
     */
    private const array OPTIONS = [
        'en' => 'English',
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

    /**
     * @return array{label: string, code: string}
     */
    public static function resolve(?string $code): array
    {
        $code ??= self::default();

        if (! self::has($code)) {
            $code = self::default();
        }

        return [
            'label' => self::get($code) ?? 'English',
            'code' => $code,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function translations(string $locale): array
    {
        $translations = [];
        $langPath = lang_path($locale);

        if (! File::isDirectory($langPath)) {
            return $translations;
        }

        foreach (File::files($langPath) as $file) {
            $translations[$file->getFilenameWithoutExtension()] = require $file->getPathname();
        }

        return $translations;
    }
}
