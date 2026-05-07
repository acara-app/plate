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
        'timing_card' => [
            'title' => 'Last drink by 5:00 pm.',
            'body' => 'Stop about 6 hours before bed.',
            'cutoff_label' => '5:00 pm',
            'bedtime_label' => '11:00 pm',
            'cutoff_24h' => 17,
            'bedtime_24h' => 23,
        ],
        'guidance_list' => [
            'title' => 'Next steps',
            'items' => ['Stay under the limit.', 'Choose decaf if symptoms show up.'],
        ],
        'safety_note' => [
            'title' => 'Safety note',
            'body' => 'Ask a clinician for medical guidance.',
            'items' => ['Pregnancy', 'Medication interactions'],
        ],
        'condition_sections' => [
            [
                'condition' => 'anxiety',
                'title' => 'Anxiety note',
                'body' => 'Caffeine can worsen anxiety symptoms.',
                'tone' => 'blue',
                'link_url' => null,
                'link_label' => null,
            ],
        ],
    ]);

    $spec = (new BuildCaffeineGuidanceSpec)->handle($guidance);

    expect($spec['root'])->toBe('root')
        ->and($spec['elements']['root']['type'])->toBe('Stack')
        ->and($spec['elements']['root']['children'])->toBe(['verdict', 'gauge', 'drinks', 'timing', 'guidance', 'condition_0', 'safety'])
        ->and($spec['elements']['verdict']['type'])->toBe('VerdictCard')
        ->and($spec['elements']['gauge']['type'])->toBe('LimitGauge')
        ->and($spec['elements']['drinks']['type'])->toBe('DrinkSizeGrid')
        ->and($spec['elements']['drinks']['props'])->toBe(['limit_mg' => 200])
        ->and($spec['elements']['timing']['type'])->toBe('TimingCard')
        ->and($spec['elements']['timing']['props'])->toHaveKeys(['title', 'body', 'cutoff_label', 'bedtime_label', 'cutoff_24h', 'bedtime_24h'])
        ->and($spec['elements']['guidance']['type'])->toBe('GuidanceList')
        ->and($spec['elements']['condition_0']['type'])->toBe('ConditionCard')
        ->and($spec['elements']['safety']['type'])->toBe('SafetyNote')
        ->and($spec['elements'])->not->toHaveKey('context');
});
