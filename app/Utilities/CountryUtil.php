<?php

declare(strict_types=1);

namespace App\Utilities;

use DateTimeZone;
use Exception;
use IntlTimeZone;

final class CountryUtil
{
    private const string UNKNOWN = '??';

    public static function fromTimezone(?string $timezone): ?string
    {
        if ($timezone === null || $timezone === '') {
            return null;
        }

        try {
            $location = new DateTimeZone($timezone)->getLocation();
        } catch (Exception) {
            return null;
        }

        $country = $location === false ? self::UNKNOWN : $location['country_code'];

        if ($country === self::UNKNOWN && class_exists(IntlTimeZone::class)) {
            $country = IntlTimeZone::getRegion($timezone) ?: self::UNKNOWN;
        }

        return preg_match('/^[A-Z]{2}$/', $country) === 1 ? $country : null;
    }
}
