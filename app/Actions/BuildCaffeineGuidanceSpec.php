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
        $elements = [
            'root' => [
                'type' => 'Stack',
                'props' => (object) [],
                'children' => ['verdict', 'gauge', 'guidance'],
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
            'guidance' => [
                'type' => 'GuidanceList',
                'props' => $guidance->guidanceList,
                'children' => [],
            ],
        ];

        if ($guidance->contextNote !== null) {
            $elements['root']['children'][] = 'context';
            $elements['context'] = [
                'type' => 'ContextNote',
                'props' => $guidance->contextNote,
                'children' => [],
            ];
        }

        $elements['root']['children'][] = 'safety';
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
