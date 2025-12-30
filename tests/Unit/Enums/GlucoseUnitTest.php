<?php

declare(strict_types=1);

use App\Enums\GlucoseUnit;

it('returns correct placeholder for mg/dL', function (): void {
    expect(GlucoseUnit::MgDl->placeholder())->toBe('e.g., 120');
});

it('returns correct placeholder for mmol/L', function (): void {
    expect(GlucoseUnit::MmolL->placeholder())->toBe('e.g., 6.7');
});

it('returns label matching value', function (): void {
    expect(GlucoseUnit::MgDl->label())->toBe('mg/dL');
    expect(GlucoseUnit::MmolL->label())->toBe('mmol/L');
});

it('converts mg/dL to mmol/L correctly', function (): void {
    expect(GlucoseUnit::mgDlToMmolL(180))->toBe(10.0);
    expect(GlucoseUnit::mgDlToMmolL(100))->toBe(5.5);
});

it('converts mmol/L to mg/dL correctly', function (): void {
    expect(GlucoseUnit::mmolLToMgDl(10.0))->toBe(180.0);
    expect(GlucoseUnit::mmolLToMgDl(5.5))->toBe(99.0);
});
