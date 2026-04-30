<?php

declare(strict_types=1);

use App\Ai\Agents\CaffeineGuidanceAgent;
use App\Data\CaffeineGuidanceData;
use App\Data\CaffeineLimitData;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;

covers(CaffeineGuidanceAgent::class);

beforeEach(function (): void {
    $this->agent = new CaffeineGuidanceAgent;
});

it('has conservative caffeine guidance instructions', function (): void {
    $instructions = $this->agent->instructions();

    expect($instructions)
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
    CaffeineGuidanceAgent::fake([[
        'summary' => 'Keep caffeine under 200 mg today.',
        'verdict_card' => [
            'title' => '200 mg is your ceiling',
            'body' => 'Your context calls for a lower caffeine limit.',
            'badge' => 'High sensitivity',
            'tone' => 'amber',
            'limit_mg' => 200,
        ],
        'limit_gauge' => [
            'label' => 'Daily caffeine limit',
            'value_label' => '200 mg',
            'limit_mg' => 200,
            'max_mg' => 400,
            'tone' => 'amber',
            'caption' => 'Adjusted from the EFSA weight-based guideline.',
        ],
        'guidance_list' => [
            'title' => 'Next steps',
            'items' => ['Stay below 200 mg.', 'Stop if symptoms appear.'],
        ],
        'safety_note' => [
            'title' => 'Safety note',
            'body' => 'This is educational guidance.',
            'items' => ['Ask a clinician about medication interactions.', 'Stop if you get chest pain.'],
        ],
        'condition_sections' => [
            [
                'condition' => 'pregnancy',
                'title' => 'Pregnancy guidance',
                'body' => 'ACOG recommends staying under 200 mg per day.',
                'tone' => 'green',
                'link_url' => 'https://www.acog.org/womens-health/faqs/moderate-caffeine-consumption-during-pregnancy',
                'link_label' => 'ACOG guidance',
            ],
        ],
    ]]);

    $limit = new CaffeineLimitData(
        heightCm: 170,
        weightKg: 70,
        age: 30,
        sex: 'female',
        sensitivity: 'high',
        sensitivityLabel: 'High sensitivity',
        limitMg: 200,
        status: 'limited',
        hasCautionContext: true,
        contextLabel: 'Pregnancy or breastfeeding',
        reasons: ['Pregnancy context lowers the cap.'],
        sourceSummary: 'EFSA weight-based guideline.',
        formulaUsed: 'efsa_weight_based',
        conditions: ['pregnancy'],
    );

    $result = $this->agent->assess($limit, 'pregnant and one latte most mornings');

    expect($result)
        ->toBeInstanceOf(CaffeineGuidanceData::class)
        ->summary->toBe('Keep caffeine under 200 mg today.')
        ->verdictCard->toHaveKey('limit_mg', 200)
        ->safetyNote->toHaveKey('items')
        ->and($result->conditionSections)->toHaveCount(1)
        ->and($result->conditionSections[0])->toHaveKey('link_url', 'https://www.acog.org/womens-health/faqs/moderate-caffeine-consumption-during-pregnancy');
});
