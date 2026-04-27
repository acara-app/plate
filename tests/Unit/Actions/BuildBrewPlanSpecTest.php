<?php

declare(strict_types=1);

use App\Actions\BuildBrewPlanSpec;
use App\Data\BrewPlanData;

covers(BuildBrewPlanSpec::class);

it('builds a Stack-rooted spec with one element per block', function (): void {
    $plan = BrewPlanData::from([
        'summary' => 'Test plan',
        'blocks' => [
            ['type' => 'Hero', 'props' => ['title' => 'A', 'subtitle' => 'B']],
            ['type' => 'Stat', 'props' => ['label' => 'mg', 'value' => '150', 'tone' => 'good']],
            ['type' => 'Tip', 'props' => ['title' => 'Hydrate', 'body' => 'water alongside']],
        ],
    ]);

    $spec = (new BuildBrewPlanSpec)->handle($plan);

    expect($spec['root'])->toBe('root')
        ->and($spec['elements']['root']['type'])->toBe('Stack')
        ->and($spec['elements']['root']['children'])->toBe(['b0', 'b1', 'b2'])
        ->and($spec['elements']['b0']['type'])->toBe('Hero')
        ->and($spec['elements']['b0']['props'])->toBe(['title' => 'A', 'subtitle' => 'B'])
        ->and($spec['elements']['b1']['type'])->toBe('Stat')
        ->and($spec['elements']['b2']['type'])->toBe('Tip');
});

it('produces an empty children list when there are no blocks', function (): void {
    $plan = BrewPlanData::from([
        'summary' => 'Empty',
        'blocks' => [],
    ]);

    $spec = (new BuildBrewPlanSpec)->handle($plan);

    expect($spec['elements']['root']['children'])->toBe([])
        ->and(array_keys($spec['elements']))->toBe(['root']);
});
