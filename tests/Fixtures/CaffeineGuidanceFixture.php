<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Data\CaffeineLimitData;

final class CaffeineGuidanceFixture
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function limit(array $overrides = []): CaffeineLimitData
    {
        return new CaffeineLimitData(...array_merge([
            'heightCm' => 170,
            'weightKg' => 70,
            'age' => 30,
            'sex' => 'female',
            'sensitivity' => 'high',
            'sensitivityLabel' => 'High sensitivity',
            'limitMg' => 200,
            'status' => 'limited',
            'hasCautionContext' => true,
            'contextLabel' => 'Pregnancy or breastfeeding',
            'reasons' => ['Pregnancy context lowers the cap.'],
            'sourceSummary' => 'EFSA weight-based guideline.',
            'formulaUsed' => 'efsa_weight_based',
            'conditions' => ['pregnancy'],
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function response(array $overrides = []): array
    {
        return array_replace_recursive([
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
        ], $overrides);
    }
}
