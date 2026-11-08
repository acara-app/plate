<?php

declare(strict_types=1);

namespace App\Enums;

enum ConsentPurpose: string
{
    case ImproveFoodRecognition = 'improve_food_recognition';

    public function version(): string
    {
        return match ($this) {
            self::ImproveFoodRecognition => '2026-11-08',
        };
    }
}
