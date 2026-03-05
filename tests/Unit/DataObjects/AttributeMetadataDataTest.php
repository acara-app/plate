<?php

declare(strict_types=1);

use App\DataObjects\AttributeMetadataData;

describe('AttributeMetadataData', function (): void {
    it('hydrates from a snake_case array', function (): void {
        $data = AttributeMetadataData::from([
            'safety_level' => 'critical',
            'dietary_rules' => ['Avoid all gluten-containing grains'],
            'foods_to_avoid' => ['Wheat', 'Barley', 'Rye'],
            'foods_to_prioritize' => ['Rice', 'Quinoa'],
            'carb_limit_per_meal_g' => 45,
            'min_fibre_per_meal_g' => 10,
            'hidden_sources' => ['Soy sauce', 'Malt vinegar'],
            'requirements' => ['Certified gluten-free label required'],
            'general_advice' => 'Check all labels carefully.',
        ]);

        expect($data->safetyLevel)->toBe('critical')
            ->and($data->dietaryRules)->toBe(['Avoid all gluten-containing grains'])
            ->and($data->foodsToAvoid)->toBe(['Wheat', 'Barley', 'Rye'])
            ->and($data->foodsToPrioritize)->toBe(['Rice', 'Quinoa'])
            ->and($data->carbLimitPerMealG)->toBe(45)
            ->and($data->minFibrePerMealG)->toBe(10)
            ->and($data->hiddenSources)->toBe(['Soy sauce', 'Malt vinegar'])
            ->and($data->requirements)->toBe(['Certified gluten-free label required'])
            ->and($data->generalAdvice)->toBe('Check all labels carefully.');
    });

    it('leaves optional fields null when omitted', function (): void {
        $data = AttributeMetadataData::from([
            'safety_level' => 'info',
        ]);

        expect($data->safetyLevel)->toBe('info')
            ->and($data->dietaryRules)->toBeNull()
            ->and($data->foodsToAvoid)->toBeNull()
            ->and($data->foodsToPrioritize)->toBeNull()
            ->and($data->carbLimitPerMealG)->toBeNull()
            ->and($data->minFibrePerMealG)->toBeNull()
            ->and($data->hiddenSources)->toBeNull()
            ->and($data->requirements)->toBeNull()
            ->and($data->generalAdvice)->toBeNull();
    });

    it('serializes to snake_case keys via toArray()', function (): void {
        $data = AttributeMetadataData::from([
            'safety_level' => 'warning',
            'foods_to_avoid' => ['Sugar'],
            'carb_limit_per_meal_g' => 60,
        ]);

        $array = $data->toArray();

        expect($array)->toHaveKey('safety_level', 'warning')
            ->and($array)->toHaveKey('foods_to_avoid', ['Sugar'])
            ->and($array)->toHaveKey('carb_limit_per_meal_g', 60)
            ->and($array)->toHaveKey('dietary_rules', null)
            ->and($array)->toHaveKey('foods_to_prioritize', null)
            ->and($array)->toHaveKey('min_fibre_per_meal_g', null)
            ->and($array)->toHaveKey('hidden_sources', null)
            ->and($array)->toHaveKey('requirements', null)
            ->and($array)->toHaveKey('general_advice', null);
    });

    it('can be constructed directly and round-trips through toArray()', function (): void {
        $original = new AttributeMetadataData(
            safetyLevel: 'critical',
            dietaryRules: ['No peanuts'],
            foodsToAvoid: ['Peanuts', 'Tree nuts'],
            foodsToPrioritize: null,
            carbLimitPerMealG: null,
            minFibrePerMealG: null,
            hiddenSources: ['Satay sauce', 'Mixed nut butter'],
            requirements: null,
            generalAdvice: 'Carry an EpiPen.',
        );

        $restored = AttributeMetadataData::from($original->toArray());

        expect($restored->safetyLevel)->toBe('critical')
            ->and($restored->dietaryRules)->toBe(['No peanuts'])
            ->and($restored->foodsToAvoid)->toBe(['Peanuts', 'Tree nuts'])
            ->and($restored->hiddenSources)->toBe(['Satay sauce', 'Mixed nut butter'])
            ->and($restored->generalAdvice)->toBe('Carry an EpiPen.');
    });
});
