<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\BrewPlanData;

final readonly class BuildBrewPlanSpec
{
    /**
     * Convert a BrewPlanData (summary + ordered blocks) into a @json-render/core
     *
     * @return array{root: string, elements: array<string, array{type: string, props: array<string, mixed>, children: array<int, string>}>}
     */
    public function handle(BrewPlanData $plan): array
    {
        $elements = [];
        $childIds = [];

        foreach ($plan->blocks as $index => $block) {
            $id = sprintf('b%d', $index);
            $childIds[] = $id;

            $elements[$id] = [
                'type' => $block->type,
                'props' => $block->props,
                'children' => [],
            ];
        }

        $elements['root'] = [
            'type' => 'Stack',
            'props' => (object) [],
            'children' => $childIds,
        ];

        return [
            'root' => 'root',
            'elements' => $elements,
        ];
    }
}
