<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\CaffeineLimitData;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class ResolveCaffeineLimit
{
    private const int ADULT_REFERENCE_HEIGHT_CM = 170;

    private const int PREGNANCY_CONTEXT_CAP_MG = 200;

    private const int ABSOLUTE_CAP_MG = 500;

    private const int ROUNDING_INCREMENT_MG = 25;

    private const float EFSA_MG_PER_KG = 3.0;

    /**
     * @var array<string, float>
     */
    private const array SENSITIVITY_MULTIPLIERS = [
        'low' => 1.0,
        'normal' => 0.75,
        'high' => 0.5,
    ];

    /**
     * @var array<string, string>
     */
    private const array SENSITIVITY_LABELS = [
        'low' => 'Low sensitivity',
        'normal' => 'Normal sensitivity',
        'high' => 'High sensitivity',
    ];

    /**
     * @param  array<int, string>  $conditions
     */
    public function handle(
        int $heightCm,
        float $weightKg,
        int $age,
        string $sex,
        string $sensitivity,
        ?string $context = null,
        array $conditions = [],
    ): CaffeineLimitData {
        throw_if($heightCm < 90 || $heightCm > 230, InvalidArgumentException::class, 'Height must be between 90 and 230 centimeters.');
        throw_if($weightKg < 30 || $weightKg > 300, InvalidArgumentException::class, 'Weight must be between 30 and 300 kilograms.');
        throw_if($age < 13 || $age > 120, InvalidArgumentException::class, 'Age must be between 13 and 120 years.');
        throw_unless(in_array($sex, ['male', 'female', 'decline'], true), InvalidArgumentException::class, 'Sex must be male, female, or decline.');
        throw_unless(array_key_exists($sensitivity, self::SENSITIVITY_MULTIPLIERS), InvalidArgumentException::class, 'Sensitivity must be low, normal, or high.');

        $detectedConditions = $this->detectConditions($context);
        $allConditions = array_values(array_unique(array_merge($detectedConditions, $conditions)));
        $hasCautionContext = $this->hasCautionContext($allConditions);

        $efsaBaseMg = $weightKg * self::EFSA_MG_PER_KG;
        $adjustedMg = $this->weightBasedLimit($weightKg, $age, $sex, $heightCm);

        if ($hasCautionContext) {
            $adjustedMg = min($adjustedMg, (float) self::PREGNANCY_CONTEXT_CAP_MG);
        }

        $limitMg = $this->roundToIncrement($adjustedMg * self::SENSITIVITY_MULTIPLIERS[$sensitivity]);
        $limitMg = min($limitMg, self::ABSOLUTE_CAP_MG);

        return new CaffeineLimitData(
            heightCm: $heightCm,
            weightKg: $weightKg,
            age: $age,
            sex: $sex,
            sensitivity: $sensitivity,
            sensitivityLabel: self::SENSITIVITY_LABELS[$sensitivity],
            limitMg: $limitMg,
            status: $hasCautionContext ? 'context_limited' : 'weight_adjusted_limit',
            hasCautionContext: $hasCautionContext,
            contextLabel: $hasCautionContext ? $this->buildContextLabel($allConditions) : null,
            reasons: $this->buildReasons($heightCm, $weightKg, $age, $sex, $sensitivity, $hasCautionContext, $efsaBaseMg, $adjustedMg, $limitMg),
            sourceSummary: 'EFSA guideline uses 3 mg per kg of body weight as a safe daily intake reference, adjusted for age, sex, and height before sensitivity and context adjustment.',
            formulaUsed: 'efsa_weight_based',
            conditions: $allConditions,
        );
    }

    private function weightBasedLimit(float $weightKg, int $age, string $sex, int $heightCm): float
    {
        $baseLimit = $weightKg * self::EFSA_MG_PER_KG;

        $sexModifier = 1.0;
        if ($sex === 'male' && $age < 65) {
            $sexModifier = 0.95;
        } elseif ($age >= 65) {
            $sexModifier = 0.80;
        } elseif ($sex === 'female' && $age >= 50) {
            $sexModifier = 0.85;
        }

        $heightModifier = 1.0;
        $ratio = $heightCm / self::ADULT_REFERENCE_HEIGHT_CM;
        if ($ratio < 0.9) {
            $heightModifier += ($ratio - 0.9) * 0.5;
        } elseif ($ratio > 1.1) {
            $heightModifier += ($ratio - 1.1) * 0.5;
        }

        return $baseLimit * $sexModifier * $heightModifier;
    }

    /**
     * @return array<int, string>
     */
    private function buildReasons(
        int $heightCm,
        float $weightKg,
        int $age,
        string $sex,
        string $sensitivity,
        bool $hasCautionContext,
        float $efsaBaseMg,
        float $adjustedMg,
        int $limitMg,
    ): array {
        $reasons = [
            sprintf('EFSA recommends 3 mg per kg, so your weight of %.1f kg gives a base of %d mg.', $weightKg, (int) round($efsaBaseMg)),
        ];

        if ($sex !== 'decline') {
            $reasons[] = sprintf('Being %s and %d years old adjusts the metabolism factor.', $sex, $age);
        }

        if ($heightCm !== self::ADULT_REFERENCE_HEIGHT_CM) {
            $reasons[] = sprintf('Your height of %d cm provides a small body-size adjustment.', $heightCm);
        }

        if ($hasCautionContext) {
            $reasons[] = sprintf('Pregnancy or related context lowers the cap to %d mg before sensitivity.', (int) round($adjustedMg));
        }

        $reasons[] = sprintf('Your %s sensitivity setting adjusts the limit to %d mg.', $sensitivity, $limitMg);

        if ($limitMg === self::ABSOLUTE_CAP_MG) {
            $reasons[] = sprintf('Capped at the daily safety ceiling of %d mg.', self::ABSOLUTE_CAP_MG);
        }

        return $reasons;
    }

    private function roundToIncrement(float $value): int
    {
        return (int) round($value / self::ROUNDING_INCREMENT_MG) * self::ROUNDING_INCREMENT_MG;
    }

    /**
     * @param  array<int, string>  $conditions
     */
    private function hasCautionContext(array $conditions): bool
    {
        $cautionConditions = ['pregnancy', 'breastfeeding', 'trying_to_conceive'];

        return array_intersect($conditions, $cautionConditions) !== [];
    }

    /**
     * @return array<int, string>
     */
    private function detectConditions(?string $context): array
    {
        if ($context === null || mb_trim($context) === '') {
            return [];
        }

        $lower = Str::of($context)->lower()->toString();
        $conditions = [];

        if (Str::contains($lower, ['pregnant', 'pregnancy'])
            && ! Str::contains($lower, ['not pregnant', 'no pregnancy'])) {
            $conditions[] = 'pregnancy';
        }

        if (Str::contains($lower, ['breastfeeding', 'breast feeding', 'nursing'])
            && ! Str::contains($lower, ['not breastfeeding', 'not breast feeding', 'not nursing'])) {
            $conditions[] = 'breastfeeding';
        }

        if (Str::contains($lower, ['trying to conceive', 'trying for pregnancy', 'planning pregnancy', 'ttc'])
            && ! Str::contains($lower, ['not trying to conceive', 'not ttc'])) {
            $conditions[] = 'trying_to_conceive';
        }

        if (Str::contains($lower, ['heart condition', 'heart problem', 'cardiac', 'heart disease', 'arrhythmia', 'afib'])) {
            $conditions[] = 'heart_condition';
        }

        if (Str::contains($lower, ['anxiety', 'anxious', 'panic attack'])) {
            $conditions[] = 'anxiety';
        }

        if (Str::contains($lower, ['gerd', 'acid reflux', 'heartburn'])) {
            $conditions[] = 'gerd';
        }

        if (Str::contains($lower, ['insomnia', 'sleep problem', "can't sleep", 'trouble sleeping'])) {
            $conditions[] = 'insomnia';
        }

        if (Str::contains($lower, ['medication', 'medicine', 'drug', 'prescription', 'blood thinner', 'beta blocker'])) {
            $conditions[] = 'medication';
        }

        return array_values(array_unique($conditions));
    }

    /**
     * @param  array<int, string>  $conditions
     */
    private function buildContextLabel(array $conditions): string
    {
        $labels = [
            'pregnancy' => 'pregnancy',
            'breastfeeding' => 'breastfeeding',
            'trying_to_conceive' => 'trying to conceive',
            'heart_condition' => 'heart condition',
            'anxiety' => 'anxiety',
            'gerd' => 'GERD or acid reflux',
            'insomnia' => 'insomnia or sleep issues',
            'medication' => 'medication use',
        ];

        $matched = array_filter(
            $conditions,
            fn (string $condition): bool => array_key_exists($condition, $labels)
        );

        if ($matched === []) {
            return 'Context detected';
        }

        $humanLabels = array_map(
            fn (string $condition): string => $labels[$condition],
            $matched
        );

        $last = array_pop($humanLabels);

        return $humanLabels === []
            ? ucfirst($last).' context detected'
            : ucfirst(implode(', ', $humanLabels)).' and '.$last.' context detected';
    }
}
