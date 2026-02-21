<?php

declare(strict_types=1);

use App\Enums\PreferredLanguage;

it('has correct values', function (): void {
    expect(PreferredLanguage::English->value)->toBe('en')
        ->and(PreferredLanguage::French->value)->toBe('fr')
        ->and(PreferredLanguage::Mongolian->value)->toBe('mn');
});

it('returns correct labels', function (): void {
    expect(PreferredLanguage::English->label())->toBe('English')
        ->and(PreferredLanguage::French->label())->toBe('Français')
        ->and(PreferredLanguage::Mongolian->label())->toBe('Монгол');
});

it('returns toArray with correct structure', function (): void {
    $array = PreferredLanguage::toArray();

    expect($array)->toBe([
        'en' => 'English',
        'fr' => 'Français',
        'mn' => 'Монгол',
    ]);
});

it('can be cast from string', function (): void {
    expect(PreferredLanguage::tryFrom('en'))->toBe(PreferredLanguage::English)
        ->and(PreferredLanguage::tryFrom('fr'))->toBe(PreferredLanguage::French)
        ->and(PreferredLanguage::tryFrom('mn'))->toBe(PreferredLanguage::Mongolian);
});
