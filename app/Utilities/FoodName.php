<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Support\Str;

final class FoodName
{
    public static function normalize(string $name): string
    {
        $ascii = Str::ascii($name);
        $lower = mb_strtolower(mb_trim($ascii));
        $cleaned = preg_replace('/[^a-z0-9 ]+/', ' ', $lower) ?? '';
        $collapsed = preg_replace('/\s+/', ' ', $cleaned) ?? '';

        return mb_trim($collapsed);
    }
}
