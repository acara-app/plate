<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\CaffeineGuidanceData;

final readonly class BuildCaffeineGuidanceSpec
{
    /**
     * @return array{root: string, elements: array<string, array{type: string, props: array<string, mixed>|object, children: array<int, string>}>}
     */
    public function handle(CaffeineGuidanceData $guidance): array
    {
        $children = ['verdict', 'gauge', 'drinks', 'guidance'];

        foreach (($guidance->conditionSections ?? []) as $index => $section) {
            $children[] = 'condition_'.$index;
        }

        $children[] = 'safety';

        /** @var array<string, array{type: string, props: array<string, mixed>|object, children: list<string>}> $elements */
        $elements = [
            'root' => [
                'type' => 'Stack',
                'props' => (object) [],
                'children' => $children,
            ],
            'verdict' => [
                'type' => 'VerdictCard',
                'props' => $guidance->verdictCard,
                'children' => [],
            ],
            'gauge' => [
                'type' => 'LimitGauge',
                'props' => $guidance->limitGauge,
                'children' => [],
            ],
            'drinks' => [
                'type' => 'DrinkSizeGrid',
                'props' => [
                    'limit_mg' => $guidance->limitGauge['limit_mg'] ?? null,
                ],
                'children' => [],
            ],
            'guidance' => [
                'type' => 'GuidanceList',
                'props' => $guidance->guidanceList,
                'children' => [],
            ],
        ];

        foreach (($guidance->conditionSections ?? []) as $index => $section) {
            $elements['condition_'.$index] = [
                'type' => 'ConditionCard',
                'props' => $section,
                'children' => [],
            ];
        }

        $elements['safety'] = [
            'type' => 'SafetyNote',
            'props' => $guidance->safetyNote,
            'children' => [],
        ];

        return [
            'root' => 'root',
            'elements' => $elements,
        ];
    }
}
