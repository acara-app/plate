<?php

declare(strict_types=1);

use App\Enums\IntensityChoice;

it('has correct values', function (): void {
    expect(IntensityChoice::Balanced->value)->toBe('balanced')
        ->and(IntensityChoice::Aggressive->value)->toBe('aggressive');
});

it('returns correct labels', function (IntensityChoice $choice, string $label): void {
    expect($choice->label())->toBe($label);
})->with([
    'Balanced' => [IntensityChoice::Balanced, 'Balanced (Sustainable)'],
    'Aggressive' => [IntensityChoice::Aggressive, 'Aggressive (Fast Results)'],
]);
