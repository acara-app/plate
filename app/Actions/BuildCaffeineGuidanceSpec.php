<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\CaffeineGuidance\ConditionSectionData;
use App\Data\CaffeineGuidanceData;
use App\Data\CaffeineLimitData;
use App\Utilities\LocalizedDate;

final readonly class BuildCaffeineGuidanceSpec
{
    private const string ACOG_PREGNANCY_GUIDANCE_URL = 'https://www.acog.org/womens-health/faqs/moderate-caffeine-consumption-during-pregnancy';

    /**
     * @var array<string, string>
     */
    private const array CONDITION_LINKS = [
        'pregnancy' => self::ACOG_PREGNANCY_GUIDANCE_URL,
        'breastfeeding' => self::ACOG_PREGNANCY_GUIDANCE_URL,
        'trying_to_conceive' => self::ACOG_PREGNANCY_GUIDANCE_URL,
    ];

    /**
     * @return array{root: string, elements: array<string, array{type: string, props: array<string, mixed>|object, children: list<string>}>}
     */
    public function handle(CaffeineGuidanceData $guidance, CaffeineLimitData $limit, string $locale): array
    {
        $elements = [
            'verdict' => $this->element('VerdictCard', [
                'title' => $guidance->verdictCard->title,
                'body' => $guidance->verdictCard->body,
                'badge' => $guidance->verdictCard->badge,
                'tone' => $guidance->verdictCard->tone->value,
                'limit_mg' => $limit->limitMg,
            ]),
            'gauge' => $this->element('LimitGauge', [
                'label' => $guidance->limitGauge->label,
                'tone' => $guidance->limitGauge->tone->value,
                'caption' => $guidance->limitGauge->caption,
                'limit_mg' => $limit->limitMg,
                'max_mg' => $limit->generalMaxMg,
            ]),
            'drinks' => $this->element('DrinkSizeGrid', [
                'limit_mg' => $limit->limitMg,
            ]),
            'timing' => $this->element('TimingCard', [
                'title' => $guidance->timingCard->title,
                'body' => $guidance->timingCard->body,
                'cutoff_label' => LocalizedDate::hour($limit->cutoffHour, $locale),
                'bedtime_label' => LocalizedDate::hour($limit->bedtimeHour, $locale),
                'cutoff_24h' => $limit->cutoffHour,
                'bedtime_24h' => $limit->bedtimeHour,
            ]),
            'guidance' => $this->element('GuidanceList', [
                'title' => $guidance->guidanceList->title,
                'items' => $guidance->guidanceList->items,
            ]),
        ];

        foreach ($this->detectedConditionSections($guidance, $limit) as $index => $section) {
            $elements["condition_{$index}"] = $this->element('ConditionCard', [
                'condition' => $section->condition,
                'title' => $section->title,
                'body' => $section->body,
                'tone' => $section->tone->value,
                ...$this->conditionLink($section->condition, $locale),
            ]);
        }

        $elements['safety'] = $this->element('SafetyNote', [
            'title' => $guidance->safetyNote->title,
            'body' => $guidance->safetyNote->body,
            'items' => $guidance->safetyNote->items,
        ]);

        return [
            'root' => 'root',
            'elements' => [
                'root' => [
                    'type' => 'Stack',
                    'props' => (object) [],
                    'children' => array_keys($elements),
                ],
                ...$elements,
            ],
        ];
    }

    /**
     * @return list<ConditionSectionData>
     */
    private function detectedConditionSections(CaffeineGuidanceData $guidance, CaffeineLimitData $limit): array
    {
        return array_values(array_filter(
            $guidance->conditionSections ?? [],
            fn (ConditionSectionData $section): bool => in_array($section->condition, $limit->conditions, true),
        ));
    }

    /**
     * @return array{link_url: string|null, link_label: string|null}
     */
    private function conditionLink(string $condition, string $locale): array
    {
        $url = self::CONDITION_LINKS[$condition] ?? null;

        return [
            'link_url' => $url,
            'link_label' => $url === null ? null : (string) __('caffeine.condition_link_acog', [], $locale),
        ];
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array{type: string, props: array<string, mixed>, children: list<string>}
     */
    private function element(string $type, array $props): array
    {
        return [
            'type' => $type,
            'props' => $props,
            'children' => [],
        ];
    }
}
