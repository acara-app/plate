<?php

declare(strict_types=1);

use App\Ai\Agents\CaffeineGuidanceAgent;
use App\Data\CaffeineGuidanceData;
use App\Utilities\LanguageUtil;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Str;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\Fixtures\CaffeineGuidanceFixture;

covers(CaffeineGuidanceAgent::class);

beforeEach(function (): void {
    $this->agent = new CaffeineGuidanceAgent;
});

it('has conservative caffeine guidance instructions', function (): void {
    expect($this->agent->instructions())
        ->toContain('deterministic caffeine limits')
        ->toContain('personalize the wording only')
        ->toContain('structured response requested by the schema');
});

it('defines schema fields matching the caffeine guidance data shape', function (): void {
    $schema = $this->agent->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKeys([
        'summary',
        'verdict_card',
        'limit_gauge',
        'timing_card',
        'guidance_list',
        'safety_note',
        'condition_sections',
    ])->and($schema)->not->toHaveKey('context_note')
        ->and($schema)->not->toHaveKey('drink_sizes');

    $verdictCard = $schema['verdict_card']->toArray();
    $limitGauge = $schema['limit_gauge']->toArray();
    $timingCard = $schema['timing_card']->toArray();
    $guidanceList = $schema['guidance_list']->toArray();
    $safetyNote = $schema['safety_note']->toArray();
    $conditionSections = $schema['condition_sections']->toArray();

    expect($verdictCard['properties'])->toHaveKeys(['title', 'body', 'badge', 'tone'])
        ->and($verdictCard['additionalProperties'])->toBeFalse()
        ->and($limitGauge['properties'])->toHaveKeys(['label', 'tone', 'caption'])
        ->and($timingCard['properties'])->toHaveKeys(['title', 'body'])
        ->and($timingCard['additionalProperties'])->toBeFalse()
        ->and($guidanceList['properties'])->toHaveKeys(['title', 'items'])
        ->and($guidanceList['properties']['items']['minItems'])->toBe(2)
        ->and($guidanceList['properties']['items']['maxItems'])->toBe(4)
        ->and($safetyNote['properties'])->toHaveKeys(['title', 'body', 'items'])
        ->and($safetyNote['properties']['items']['minItems'])->toBe(2)
        ->and($safetyNote['properties']['items']['maxItems'])->toBe(3)
        ->and($conditionSections['items']['properties'])->toHaveKeys(['condition', 'title', 'body', 'tone']);
});

it('keeps the schema snake_case keys aligned with the DTO camelCase properties', function (): void {
    $schema = (new CaffeineGuidanceAgent)->schema(new JsonSchemaTypeFactory);

    $dtoProperties = array_map(
        fn (ReflectionParameter $parameter): string => $parameter->getName(),
        new ReflectionClass(CaffeineGuidanceData::class)->getConstructor()?->getParameters() ?? [],
    );

    $schemaPropertiesAsCamelCase = array_map(
        Str::camel(...),
        array_keys($schema),
    );

    sort($dtoProperties);
    sort($schemaPropertiesAsCamelCase);

    expect($schemaPropertiesAsCamelCase)->toBe($dtoProperties);
});

it('assesses deterministic limits with structured agent output', function (): void {
    CaffeineGuidanceAgent::fake([CaffeineGuidanceFixture::response()]);

    $result = $this->agent->assess(CaffeineGuidanceFixture::limit(), 'pregnant and one latte most mornings');

    expect($result)
        ->toBeInstanceOf(CaffeineGuidanceData::class)
        ->summary->toBe('Keep caffeine under 200 mg today.')
        ->and($result->verdictCard->title)->toBe('200 mg is your ceiling')
        ->and($result->conditionSections)->toHaveCount(1);
});

it('asks for every text value in the requested language', function (): void {
    CaffeineGuidanceAgent::fake([CaffeineGuidanceFixture::response()]);

    $this->agent->assess(CaffeineGuidanceFixture::limit(), null, 'fr');

    CaffeineGuidanceAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Write every text value in '.LanguageUtil::get('fr')));
});
