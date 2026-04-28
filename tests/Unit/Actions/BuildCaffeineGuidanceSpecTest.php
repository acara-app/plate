<?php

declare(strict_types=1);

use App\Actions\BuildCaffeineGuidanceSpec;
use App\Data\CaffeineGuidanceData;

covers(BuildCaffeineGuidanceSpec::class);

it('builds a json-render spec matching the caffeine guidance catalog', function (): void {
    $guidance = CaffeineGuidanceData::from([
        'summary' => 'Keep caffeine under 200 mg.',
        'verdict_card' => [
            'title' => '200 mg is your limit',
            'body' => 'Anything above this is likely too much today.',
            'badge' => 'High sensitivity',
            'tone' => 'red',
            'limit_mg' => 200,
        ],
        'limit_gauge' => [
            'label' => 'Daily caffeine limit',
            'value_label' => '200 mg',
            'limit_mg' => 200,
            'max_mg' => 400,
            'tone' => 'red',
            'caption' => 'Adjusted for sensitivity.',
        ],
        'guidance_list' => [
            'title' => 'Next steps',
            'items' => ['Stay under the limit.', 'Choose decaf if symptoms show up.'],
        ],
        'context_note' => [
            'title' => 'Context note',
            'body' => 'Your context changed the wording.',
        ],
        'safety_note' => [
            'title' => 'Safety note',
            'body' => 'Ask a clinician for medical guidance.',
            'items' => ['Pregnancy', 'Medication interactions'],
        ],
    ]);

    $spec = (new BuildCaffeineGuidanceSpec)->handle($guidance);

    expect($spec['root'])->toBe('root')
        ->and($spec['elements']['root']['type'])->toBe('Stack')
        ->and($spec['elements']['root']['children'])->toBe(['verdict', 'gauge', 'guidance', 'context', 'safety'])
        ->and($spec['elements']['verdict']['type'])->toBe('VerdictCard')
        ->and($spec['elements']['verdict']['props']['limit_mg'])->toBe(200)
        ->and($spec['elements']['gauge']['type'])->toBe('LimitGauge')
        ->and($spec['elements']['guidance']['type'])->toBe('GuidanceList')
        ->and($spec['elements']['context']['type'])->toBe('ContextNote')
        ->and($spec['elements']['safety']['type'])->toBe('SafetyNote');
});
