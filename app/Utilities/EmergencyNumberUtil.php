<?php

declare(strict_types=1);

namespace App\Utilities;

use DateTimeZone;
use Exception;

final class EmergencyNumberUtil
{
    /**
     * @var array<string, string>
     */
    private const array EMERGENCY_NUMBERS = [
        'US' => '911',
        'CA' => '911',
        'MX' => '911',
        'GB' => '999',
        'AU' => '000',
        'NZ' => '111',
        'JP' => '119',
        'KR' => '119',
        'CN' => '120',
        'IN' => '112',
        'MN' => '103',
        'BR' => '192',
        'ZA' => '10177',
        'RU' => '103',
        'TR' => '112',
        'SA' => '997',
        'AE' => '998',
        'PH' => '911',
        'TH' => '1669',
        'SG' => '995',
        'MY' => '999',
        'ID' => '118',
        'VN' => '115',
    ];

    private const string EU_NUMBER = '112';

    /**
     * @var array<int, string>
     */
    private const array EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
        'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'NO', 'CH', 'IS',
    ];

    public static function countryFromTimezone(string $timezone): string
    {
        try {
            $tz = new DateTimeZone($timezone);
            $location = $tz->getLocation();

            if ($location !== false && $location['country_code'] !== '??') {
                return $location['country_code'];
            }
        } catch (Exception) {
            //
        }

        return 'US';
    }

    public static function emergencyNumber(string $timezone): string
    {
        $country = self::countryFromTimezone($timezone);

        return self::EMERGENCY_NUMBERS[$country]
            ?? (in_array($country, self::EU_COUNTRIES, true) ? self::EU_NUMBER : '112 (international)');
    }
}
