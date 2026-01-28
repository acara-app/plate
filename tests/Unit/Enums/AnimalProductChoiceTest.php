<?php

declare(strict_types=1);

use App\Enums\AnimalProductChoice;

it('has correct values', function (): void {
    expect(AnimalProductChoice::Omnivore->value)->toBe('omnivore')
        ->and(AnimalProductChoice::Pescatarian->value)->toBe('pescatarian')
        ->and(AnimalProductChoice::Vegan->value)->toBe('vegan');
});

it('returns correct labels', function (AnimalProductChoice $choice, string $label): void {
    expect($choice->label())->toBe($label);
})->with([
    'Omnivore' => [AnimalProductChoice::Omnivore, 'I love meat/fish.'],
    'Pescatarian' => [AnimalProductChoice::Pescatarian, 'I prefer plants, but eat fish/eggs.'],
    'Vegan' => [AnimalProductChoice::Vegan, 'Strictly plants only.'],
]);
