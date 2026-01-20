<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentType: string
{
    case Food = 'food';
    case UsdaDailyServingSize = 'usda_daily_serving_size';
    case UsdaSugarLimit = 'usda_sugar_limit';

    public function label(): string
    {
        return match ($this) {
            self::Food => 'Food',
            self::UsdaDailyServingSize => 'USDA Daily Serving Size',
            self::UsdaSugarLimit => 'USDA Sugar Limit',
        };
    }
}
