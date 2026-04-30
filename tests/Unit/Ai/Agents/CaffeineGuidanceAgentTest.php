<?php

declare(strict_types=1);

use App\Ai\Agents\CaffeineGuidanceAgent;
use App\Data\CaffeineGuidanceData;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Tests\Fixtures\CaffeineGuidanceFixture;

covers(CaffeineGuidanceAgent::class);

beforeEach(function (): void {
    $this->agent = new CaffeineGuidanceAgent;
});

it('has conservative caffeine guidance instructions', function (): void {
    expect($this->agent->instructions())
        ->toContain('deterministic caffeine limits')
        ->toContain('personalize the wording only')
        ->toContain('structured response requested by the schema')
        ->toContain('Do not recommend drink schedules');
});

it('has correct attributes configured', function (): void {
    $reflection = new ReflectionClass($this->agent);

    $maxTokens = $reflection->getAttributes(MaxTokens::class);
    $timeout = $reflection->getAttributes(Timeout::class);

    expect($maxTokens)->toHaveCount(1)
        ->and($maxTokens[0]->newInstance()->value)->toBe(2500)
        ->and($timeout)->toHaveCount(1)
        ->and($timeout[0]->newInstance()->value)->toBe(60);
});

it('defines schema fields matching the caffeine guidance data shape', function (): void {
    $schema = $this->agent->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKeys([
        'summary',
        'verdict_card',
        'limit_gauge',
        'guidance_list',
        'safety_note',
        'condition_sections',
    ])->and($schema)->not->toHaveKey('context_note')
        ->and($schema)->not->toHaveKey('drink_sizes');

    $verdictCard = $schema['verdict_card']->toArray();
    $limitGauge = $schema['limit_gauge']->toArray();
    $guidanceList = $schema['guidance_list']->toArray();
    $safetyNote = $schema['safety_note']->toArray();
    $conditionSections = $schema['condition_sections']->toArray();

    expect($verdictCard['properties'])->toHaveKeys(['title', 'body', 'badge', 'tone', 'limit_mg'])
        ->and($verdictCard['additionalProperties'])->toBeFalse()
        ->and($limitGauge['properties'])->toHaveKeys(['label', 'value_label', 'limit_mg', 'max_mg', 'tone', 'caption'])
        ->and($guidanceList['properties'])->toHaveKeys(['title', 'items'])
        ->and($guidanceList['properties']['items']['minItems'])->toBe(2)
        ->and($guidanceList['properties']['items']['maxItems'])->toBe(4)
        ->and($safetyNote['properties'])->toHaveKeys(['title', 'body', 'items'])
        ->and($safetyNote['properties']['items']['minItems'])->toBe(2)
        ->and($safetyNote['properties']['items']['maxItems'])->toBe(3)
        ->and($conditionSections['items']['properties'])->toHaveKeys(['condition', 'title', 'body', 'tone', 'link_url', 'link_label']);
});

it('assesses deterministic limits with structured agent output', function (): void {
    CaffeineGuidanceAgent::fake([CaffeineGuidanceFixture::response()]);

    $result = $this->agent->assess(CaffeineGuidanceFixture::limit(), 'pregnant and one latte most mornings');

    expect($result)
        ->toBeInstanceOf(CaffeineGuidanceData::class)
        ->summary->toBe('Keep caffeine under 200 mg today.')
        ->verdictCard->toHaveKey('limit_mg', 200)
        ->safetyNote->toHaveKey('items')
        ->and($result->conditionSections)->toHaveCount(1)
        ->and($result->conditionSections[0])->toHaveKey('link_url', 'https://www.acog.org/womens-health/faqs/moderate-caffeine-consumption-during-pregnancy');
});

it('assesses with mongolian locale', function (): void {
    CaffeineGuidanceAgent::fake([CaffeineGuidanceFixture::response([
        'summary' => '200 mg',
        'condition_sections' => null,
    ])]);

    $result = $this->agent->assess(CaffeineGuidanceFixture::limit(), null, 'mn');

    expect($result)->toBeInstanceOf(CaffeineGuidanceData::class);
});
