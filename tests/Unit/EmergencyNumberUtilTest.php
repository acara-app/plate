<?php

declare(strict_types=1);

use App\Utilities\EmergencyNumberUtil;

covers(EmergencyNumberUtil::class);

it('resolves US emergency number from American timezone', function (): void {
    expect(EmergencyNumberUtil::emergencyNumber('America/New_York'))->toBe('911');
});

it('resolves Mongolian emergency number from Ulaanbaatar timezone', function (): void {
    expect(EmergencyNumberUtil::emergencyNumber('Asia/Ulaanbaatar'))->toBe('103');
});

it('resolves EU emergency number from European timezone', function (): void {
    expect(EmergencyNumberUtil::emergencyNumber('Europe/Berlin'))->toBe('112');
});

it('resolves UK emergency number from London timezone', function (): void {
    expect(EmergencyNumberUtil::emergencyNumber('Europe/London'))->toBe('999');
});

it('resolves Japanese emergency number from Tokyo timezone', function (): void {
    expect(EmergencyNumberUtil::emergencyNumber('Asia/Tokyo'))->toBe('119');
});

it('falls back to US for UTC timezone', function (): void {
    expect(EmergencyNumberUtil::countryFromTimezone('UTC'))->toBe('US');
});

it('falls back to international number for unknown country', function (): void {
    expect(EmergencyNumberUtil::emergencyNumber('Pacific/Kiritimati'))->toBe('112 (international)');
});

it('falls back to US for invalid timezone', function (): void {
    expect(EmergencyNumberUtil::countryFromTimezone('Invalid/Timezone'))->toBe('US');
});

it('derives correct country code from timezone', function (string $timezone, string $expected): void {
    expect(EmergencyNumberUtil::countryFromTimezone($timezone))->toBe($expected);
})->with([
    ['America/Chicago', 'US'],
    ['Asia/Ulaanbaatar', 'MN'],
    ['Europe/Paris', 'FR'],
    ['Australia/Sydney', 'AU'],
    ['Asia/Kolkata', 'IN'],
]);
