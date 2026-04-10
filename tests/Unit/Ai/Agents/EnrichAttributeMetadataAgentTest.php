<?php

declare(strict_types=1);

use App\Ai\Agents\EnrichAttributeMetadataAgent;
use App\DataObjects\AttributeMetadataData;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Tests\Helpers\TestJsonSchema;

covers(EnrichAttributeMetadataAgent::class);

beforeEach(function (): void {
    $this->agent = new EnrichAttributeMetadataAgent;
});

it('returns instructions covering nutrition expertise', function (): void {
    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toBeString()
        ->toContain('nutritionist')
        ->toContain('dietary')
        ->toContain('health conditions')
        ->toContain('safety_level');
});

it('has correct attributes configured', function (): void {
    $reflection = new ReflectionClass($this->agent);

    $provider = $reflection->getAttributes(Provider::class);
    $maxTokens = $reflection->getAttributes(MaxTokens::class);
    $timeout = $reflection->getAttributes(Timeout::class);

    expect($provider)->toHaveCount(1)
        ->and($provider[0]->getArguments())->toBe(['gemini'])
        ->and($maxTokens)->toHaveCount(1)
        ->and($maxTokens[0]->newInstance()->value)->toBe(8192)
        ->and($timeout)->toHaveCount(1)
        ->and($timeout[0]->newInstance()->value)->toBe(60);
});

it('returns valid schema with all metadata fields', function (): void {
    $schema = new TestJsonSchema;
    $result = $this->agent->schema($schema);

    expect($result)->toBeArray()
        ->toHaveKeys([
            'safety_level',
            'dietary_rules',
            'foods_to_avoid',
            'foods_to_prioritize',
            'carb_limit_per_meal_g',
            'min_fibre_per_meal_g',
            'hidden_sources',
            'requirements',
            'general_advice',
        ]);
});

it('enriches a health condition via fake response', function (): void {
    EnrichAttributeMetadataAgent::fake([[
        'safety_level' => 'warning',
        'dietary_rules' => ['Limit carbs to 45-60g per meal'],
        'foods_to_avoid' => ['White bread', 'Sugary drinks'],
        'foods_to_prioritize' => ['Leafy greens', 'Whole grains'],
        'carb_limit_per_meal_g' => 60,
        'min_fibre_per_meal_g' => 8,
        'hidden_sources' => null,
        'requirements' => null,
        'general_advice' => 'Monitor blood sugar regularly',
    ]]);

    $result = $this->agent->enrich('health_condition', 'Type 2 Diabetes');

    expect($result)
        ->toBeInstanceOf(AttributeMetadataData::class)
        ->safetyLevel->toBe('warning')
        ->carbLimitPerMealG->toBe(60)
        ->minFibrePerMealG->toBe(8)
        ->generalAdvice->toBe('Monitor blood sugar regularly');

    expect($result->dietaryRules)->toBe(['Limit carbs to 45-60g per meal'])
        ->and($result->foodsToAvoid)->toBe(['White bread', 'Sugary drinks'])
        ->and($result->foodsToPrioritize)->toBe(['Leafy greens', 'Whole grains']);
});

it('enriches an allergy via fake response', function (): void {
    EnrichAttributeMetadataAgent::fake([[
        'safety_level' => 'critical',
        'dietary_rules' => ['Strictly avoid all peanut products'],
        'foods_to_avoid' => ['Peanut butter', 'Trail mix'],
        'foods_to_prioritize' => null,
        'carb_limit_per_meal_g' => null,
        'min_fibre_per_meal_g' => null,
        'hidden_sources' => ['Sauces', 'Baked goods', 'Asian cuisine'],
        'requirements' => null,
        'general_advice' => null,
    ]]);

    $result = $this->agent->enrich('allergy', 'Peanuts');

    expect($result)
        ->toBeInstanceOf(AttributeMetadataData::class)
        ->safetyLevel->toBe('critical')
        ->foodsToPrioritize->toBeNull()
        ->carbLimitPerMealG->toBeNull()
        ->generalAdvice->toBeNull();

    expect($result->hiddenSources)->toBe(['Sauces', 'Baked goods', 'Asian cuisine']);
});
