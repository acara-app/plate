<?php

declare(strict_types=1);

namespace App\Utilities;

use Carbon\CarbonInterface;

final class LocalizedDate
{
    public static function format(CarbonInterface $date, string $locale): string
    {
        if ($locale === 'mn') {
            $monthOrdinal = in_array($date->month, [1, 4, 9, 11], true) ? 'дүгээр' : 'дугаар';
            $daySuffix = in_array($date->day % 10, [1, 4, 9], true) ? 'ний' : 'ны';

            return "{$date->year} оны {$date->month} {$monthOrdinal} сарын {$date->day}-{$daySuffix} өдөр";
        }

        if ($locale === 'en') {
            return $date->format('F j, Y');
        }

        return $date->toImmutable()->settings(['locale' => $locale])->translatedFormat('j F Y');
    }
}
