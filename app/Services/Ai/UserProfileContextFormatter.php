<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Data\Ai\AiSafeUserProfileData;
use App\Enums\DietType;

final readonly class UserProfileContextFormatter
{
    /**
     * @param  list<string>  $sections
     */
    public function format(
        AiSafeUserProfileData $profileData,
        array $sections = [
            'biometrics',
            'dietary_preferences',
            'goals',
            'health_conditions',
            'medications',
        ],
    ): string {
        $parts = [];

        foreach ($sections as $section) {
            $part = match ($section) {
                'biometrics' => $this->formatBiometrics($profileData),
                'dietary_preferences' => $this->formatDietaryPreferences($profileData),
                'goals' => $this->formatGoals($profileData),
                'health_conditions' => $this->formatHealthConditions($profileData),
                'medications' => $this->formatMedications($profileData),
                // @codeCoverageIgnoreStart
                'household' => $this->formatHousehold($profileData),
                default => '',
                // @codeCoverageIgnoreEnd
            };

            if ($part !== '') {
                $parts[] = $part;
            }
        }

        if ($profileData->missingData !== []) {
            $fieldsList = implode(', ', $profileData->missingData);
            $parts[] = sprintf('MISSING PROFILE DATA: %s. Proceed with reasonable defaults - do NOT block the user or ask them to complete their profile first. After fulfilling their request, briefly mention that providing these details (via conversation) would allow more personalized recommendations. Use the update_user_biometrics tool if the user shares this information.', $fieldsList);
        }

        return implode("\n", $parts);
    }

    private function formatBiometrics(AiSafeUserProfileData $profileData): string
    {
        /** @var array<string, mixed> $bio */
        $bio = $profileData->section('biometrics');
        $bioParts = [];

        if (isset($bio['age']) && is_scalar($bio['age'])) {
            $bioParts[] = 'Age: '.$bio['age'];
        }

        if (isset($bio['height_cm']) && is_scalar($bio['height_cm'])) {
            $bioParts[] = 'Height: '.$bio['height_cm'].'cm';
        }

        if (isset($bio['weight_kg']) && is_scalar($bio['weight_kg'])) {
            $bioParts[] = 'Weight: '.$bio['weight_kg'].'kg';
        }

        if (isset($bio['sex']) && is_scalar($bio['sex'])) {
            $bioParts[] = 'Sex: '.$bio['sex'];
        }

        if (isset($bio['bmi']) && is_scalar($bio['bmi'])) {
            $bioParts[] = 'BMI: '.$bio['bmi'];
        }

        if (isset($bio['tdee']) && is_scalar($bio['tdee'])) {
            $bioParts[] = 'Daily Calorie Needs (TDEE): '.$bio['tdee'].' kcal';
        }

        return $bioParts === []
            ? ''
            : 'BIOMETRICS: '.implode(', ', $bioParts);
    }

    private function formatDietaryPreferences(AiSafeUserProfileData $profileData): string
    {
        return $this->formatAttributeSection(
            $profileData,
            'dietary_preferences',
            'DIETARY PREFERENCES/RESTRICTIONS',
            includeCategory: true,
        );
    }

    private function formatGoals(AiSafeUserProfileData $profileData): string
    {
        /** @var array<string, mixed> $goals */
        $goals = $profileData->section('goals');
        $goalParts = [];
        $parts = [];

        if (isset($goals['primary_goal']) && is_scalar($goals['primary_goal'])) {
            $goalParts[] = 'Primary Goal: '.$goals['primary_goal'];
        }

        if (isset($goals['target_weight_kg']) && is_scalar($goals['target_weight_kg'])) {
            $goalParts[] = 'Target Weight: '.$goals['target_weight_kg'].'kg';
        }

        if (isset($goals['additional_goals']) && is_scalar($goals['additional_goals'])) {
            $goalParts[] = 'Additional Goals: '.$goals['additional_goals'];
        }

        if (isset($goals['calculated_diet_type']) && is_scalar($goals['calculated_diet_type'])) {
            $parts[] = 'Diet Type: '.$goals['calculated_diet_type'];
            $dietType = DietType::tryFrom((string) $goals['calculated_diet_type']);

            if ($dietType instanceof DietType) {
                $macros = $dietType->macroTargets();
                $parts[] = 'Recommended Macros: '.$macros['carbs'].'% carbs, '.$macros['protein'].'% protein, '.$macros['fat'].'% fat';
            }
        }

        if ($goalParts !== []) {
            $parts[] = 'GOALS: '.implode(', ', $goalParts);
        }

        return implode("\n", $parts);
    }

    private function formatHealthConditions(AiSafeUserProfileData $profileData): string
    {
        return $this->formatAttributeSection($profileData, 'health_conditions', 'HEALTH CONDITIONS');
    }

    private function formatMedications(AiSafeUserProfileData $profileData): string
    {
        return $this->formatAttributeSection(
            $profileData,
            'medications',
            'MEDICATIONS',
            includeMedicationDetails: true,
        );
    }

    private function formatHousehold(AiSafeUserProfileData $profileData): string
    {
        /** @var array{summary?: string|null} $household */
        // @codeCoverageIgnoreStart
        $household = $profileData->section('household');
        $summary = $household['summary'] ?? null;

        if (! is_string($summary) || $summary === '') {
            return '';
        }

        return 'HOUSEHOLD/FAMILY: '.$summary;
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param  array<string, mixed>  $attribute
     */
    private function formatAttribute(
        array $attribute,
        bool $includeCategory = false,
        bool $includeMedicationDetails = false,
    ): string {
        $name = is_scalar($attribute['name'] ?? null) ? (string) $attribute['name'] : 'Unknown';
        $details = [];

        if ($includeCategory && isset($attribute['category']) && is_scalar($attribute['category'])) {
            $details[] = str_replace('_', ' ', (string) $attribute['category']);
        }

        if (isset($attribute['severity']) && is_scalar($attribute['severity'])) {
            $details[] = (string) $attribute['severity']; // @codeCoverageIgnore
        }

        if ($includeMedicationDetails && isset($attribute['metadata']) && is_array($attribute['metadata'])) {
            foreach (['dosage', 'frequency', 'purpose'] as $key) {
                $value = $attribute['metadata'][$key] ?? null;

                if (is_scalar($value) && (string) $value !== '') {
                    $details[] = str_replace('_', ' ', $key).': '.$value;
                }
            }
        }

        $suffix = $details === [] ? '' : ' ('.implode(', ', $details).')';
        $notes = isset($attribute['notes']) && is_scalar($attribute['notes']) && (string) $attribute['notes'] !== ''
            ? ': '.$attribute['notes']
            : '';

        return $name.$suffix.$notes;
    }

    private function formatAttributeSection(
        AiSafeUserProfileData $profileData,
        string $section,
        string $heading,
        bool $includeCategory = false,
        bool $includeMedicationDetails = false,
    ): string {
        /** @var array<int, array<string, mixed>> $attributes */
        $attributes = $profileData->section($section);

        if ($attributes === []) {
            return '';
        }

        $attributeStrings = array_map(
            fn (array $attribute): string => $this->formatAttribute(
                $attribute,
                $includeCategory,
                $includeMedicationDetails,
            ),
            $attributes,
        );

        return $heading.': '.implode(', ', $attributeStrings);
    }
}
