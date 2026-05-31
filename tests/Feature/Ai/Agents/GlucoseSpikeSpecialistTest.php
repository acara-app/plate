<?php

declare(strict_types=1);

use App\Ai\Agents\GlucoseSpikeSpecialist;
use App\Ai\Agents\SpikePredictorAgent;
use App\Enums\SpikeRiskLevel;
use App\Models\User;
use App\Utilities\LanguageUtil;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Tests\Helpers\TestJsonSchema;

covers(GlucoseSpikeSpecialist::class);

it('is exposed as a unique structured sub-agent tool', function (): void {
    $agent = new GlucoseSpikeSpecialist;

    $subAgentNames = collect(config()->array('plate.sub_agents', []))
        ->map(fn (string $class): string => resolve($class)->name())
        ->all();

    expect($agent)
        ->toBeInstanceOf(Agent::class)
        ->toBeInstanceOf(CanActAsTool::class)
        ->toBeInstanceOf(HasStructuredOutput::class)
        ->and($agent->name())->toBe('glucose_spike_specialist')
        ->and($agent->description())->toBeString()->not->toBe('')
        ->and($subAgentNames)->toContain('glucose_spike_specialist')
        ->and(array_count_values($subAgentNames)['glucose_spike_specialist'])->toBe(1)
        ->and($subAgentNames)->toBe(array_unique($subAgentNames));
});

it('matches the spike predictor structured schema fields', function (): void {
    $schema = new TestJsonSchema;

    $specialistFields = array_keys((new GlucoseSpikeSpecialist)->schema($schema));
    $predictorFields = array_keys((new SpikePredictorAgent)->schema($schema));

    expect($specialistFields)->toBe($predictorFields)
        ->and($specialistFields)->toBe([
            'risk_level',
            'estimated_gl',
            'explanation',
            'smart_fix',
            'spike_reduction_percentage',
        ]);
});

it('uses the expected glucose spike prediction guidance concepts', function (): void {
    $instructions = (new GlucoseSpikeSpecialist)->instructions();

    expect($instructions)
        ->toContain('glycemic index')
        ->toContain('glycemic load')
        ->toContain('portion size')
        ->toContain('protein, fat, fiber')
        ->toContain('worried about blood sugar spikes')
        ->toContain('COMPARISON')
        ->toContain('risk_level')
        ->toContain('smart_fix');
});

it('localizes the explanation guidance from the authenticated user locale', function (): void {
    $user = User::factory()->create(['locale' => 'mn']);
    $this->actingAs($user);

    ['label' => $label, 'code' => $code] = LanguageUtil::resolve('mn');

    expect((new GlucoseSpikeSpecialist)->instructions())
        ->toContain($label)
        ->toContain(sprintf('language code: `%s`', $code));
});

it('has the same runtime limits as the spike predictor', function (): void {
    $reflection = new ReflectionClass(GlucoseSpikeSpecialist::class);

    $maxTokens = $reflection->getAttributes(MaxTokens::class);
    $timeout = $reflection->getAttributes(Timeout::class);

    expect($maxTokens)->toHaveCount(1)
        ->and($maxTokens[0]->newInstance()->value)->toBe(2000)
        ->and($timeout)->toHaveCount(1)
        ->and($timeout[0]->newInstance()->value)->toBe(120);
});

it('returns a structured glucose spike risk result shape', function (): void {
    GlucoseSpikeSpecialist::fake([[
        'risk_level' => 'high',
        'estimated_gl' => 43,
        'explanation' => 'White rice is a refined carbohydrate with a high glycemic impact.',
        'smart_fix' => 'Pair it with protein and vegetables, or choose a smaller portion.',
        'spike_reduction_percentage' => 35,
    ]]);

    $response = (new GlucoseSpikeSpecialist)->prompt('Analyze this food for glucose spike risk: "white rice"');

    expect($response->toArray())
        ->toHaveKey('risk_level', SpikeRiskLevel::High->value)
        ->toHaveKey('estimated_gl', 43)
        ->toHaveKey('explanation', 'White rice is a refined carbohydrate with a high glycemic impact.')
        ->toHaveKey('smart_fix', 'Pair it with protein and vegetables, or choose a smaller portion.')
        ->toHaveKey('spike_reduction_percentage', 35);
});
