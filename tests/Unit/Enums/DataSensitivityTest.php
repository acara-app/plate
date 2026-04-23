<?php

declare(strict_types=1);

use App\Enums\DataSensitivity;

covers(DataSensitivity::class);

it('ranks General below Personal below Sensitive', function (): void {
    expect(DataSensitivity::General->rank())->toBe(0)
        ->and(DataSensitivity::Personal->rank())->toBe(1)
        ->and(DataSensitivity::Sensitive->rank())->toBe(2);
});

describe('isAtLeast', function (): void {
    it('returns true when the value is equal to or greater than the other', function (DataSensitivity $value, DataSensitivity $other, bool $expected): void {
        expect($value->isAtLeast($other))->toBe($expected);
    })->with([
        'General >= General' => [DataSensitivity::General, DataSensitivity::General, true],
        'Personal >= General' => [DataSensitivity::Personal, DataSensitivity::General, true],
        'Sensitive >= General' => [DataSensitivity::Sensitive, DataSensitivity::General, true],
        'Sensitive >= Personal' => [DataSensitivity::Sensitive, DataSensitivity::Personal, true],
        'Personal >= Sensitive' => [DataSensitivity::Personal, DataSensitivity::Sensitive, false],
        'General >= Personal' => [DataSensitivity::General, DataSensitivity::Personal, false],
    ]);
});

describe('max', function (): void {
    it('returns General when no values are passed', function (): void {
        expect(DataSensitivity::max())->toBe(DataSensitivity::General);
    });

    it('returns the highest sensitivity from the given values', function (): void {
        expect(DataSensitivity::max(DataSensitivity::General, DataSensitivity::Sensitive, DataSensitivity::Personal))
            ->toBe(DataSensitivity::Sensitive);

        expect(DataSensitivity::max(DataSensitivity::General, DataSensitivity::Personal))
            ->toBe(DataSensitivity::Personal);

        expect(DataSensitivity::max(DataSensitivity::General))
            ->toBe(DataSensitivity::General);
    });
});
