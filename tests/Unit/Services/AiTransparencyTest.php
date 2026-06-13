<?php

declare(strict_types=1);

use App\Services\AiTransparency;

it('describes no database lookup when the reference lookup is disabled', function (): void {
    config()->set('plate.food_photo_analyzer.reference_lookup.enabled', false);

    expect(AiTransparency::usesReferenceLookup())->toBeFalse()
        ->and(implode(' ', AiTransparency::pipeline()))->toContain('not retrieved from a database')
        ->and(AiTransparency::snapToTrackFaqs()[0]['a'])->toContain('not looked up in a live database');
});

it('describes USDA-derived values when the reference lookup is enabled', function (): void {
    config()->set('plate.food_photo_analyzer.reference_lookup.enabled', true);

    $pipeline = implode(' ', AiTransparency::pipeline());

    expect(AiTransparency::usesReferenceLookup())->toBeTrue()
        ->and($pipeline)->toContain('USDA FoodData Central')
        ->and($pipeline)->toContain('reference-derived')
        ->and($pipeline)->not->toContain('not retrieved from a database')
        ->and(AiTransparency::snapToTrackFaqs()[0]['a'])->toContain('computed from USDA FoodData Central');
});

it('keeps the provenance explanation free of quantitative accuracy claims', function (): void {
    $provenance = implode(' ', AiTransparency::provenance());

    expect($provenance)
        ->toContain('Reference-derived')
        ->toContain('portion size')
        ->and(preg_match('/\d+%/', $provenance))->toBe(0);
});
