<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DietType;
use App\Models\User;
use App\Models\UserMedication;
use App\Models\UserProfile;

final readonly class GetUserProfileContextAction
{
    /**
     * Get formatted user profile context for AI consumption.
     *
     * @return array<string, mixed>
     */
    public function handle(User $user): array
    {
        $profile = $user->profile;

        if (! $profile instanceof UserProfile) {
            return [
                'onboarding_completed' => false,
                'missing_data' => ['profile'],
                'context' => 'User has not completed their profile. Biometric data, dietary preferences, health conditions, and medications are unavailable.',
                'raw_data' => null,
            ];
        }

        $context = [
            'onboarding_completed' => $profile->onboarding_completed,
            'biometrics' => $this->getBiometrics($profile),
            'dietary_preferences' => $this->getDietaryPreferences($profile),
            'health_conditions' => $this->getHealthConditions($profile),
            'medications' => $this->getMedications($profile),
            'goals' => $this->getGoals($profile),
        ];

        $missingData = $this->identifyMissingData($profile);

        return [
            'onboarding_completed' => $profile->onboarding_completed,
            'missing_data' => $missingData,
            'context' => $this->formatContextForAI($context, $missingData),
            'raw_data' => $context,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getBiometrics(UserProfile $profile): array
    {
        return [
            'age' => $profile->age,
            'height_cm' => $profile->height,
            'weight_kg' => $profile->weight,
            'sex' => $profile->sex?->value,
            'bmi' => $profile->bmi,
            'bmr' => $profile->bmr,
            'tdee' => $profile->tdee,
            'activity_multiplier' => $profile->derived_activity_multiplier,
        ];
    }

    /**
     * @return array<int, array{name: string, severity: mixed, notes: mixed}>
     */
    private function getDietaryPreferences(UserProfile $profile): array
    {
        return array_values($profile->dietaryPreferences->map(fn (\App\Models\DietaryPreference $pref): array => [
            'name' => $pref->name,
            'severity' => $pref->pivot->severity ?? null,
            'notes' => $pref->pivot->notes ?? null,
        ])->all());
    }

    /**
     * @return array<int, array{name: string, notes: mixed}>
     */
    private function getHealthConditions(UserProfile $profile): array
    {
        return array_values($profile->healthConditions->map(fn (\App\Models\HealthCondition $condition): array => [
            'name' => $condition->name,
            'notes' => $condition->pivot->notes ?? null,
        ])->all());
    }

    /**
     * @return array<int, array{name: string, dosage: mixed, frequency: mixed, purpose: mixed}>
     */
    private function getMedications(UserProfile $profile): array
    {
        return array_values($profile->medications->map(fn (UserMedication $med): array => [
            'name' => $med->name,
            'dosage' => $med->dosage,
            'frequency' => $med->frequency,
            'purpose' => $med->purpose,
        ])->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function getGoals(UserProfile $profile): array
    {
        return [
            'primary_goal' => $profile->goal_choice?->value,
            'target_weight_kg' => $profile->target_weight,
            'intensity' => $profile->intensity_choice?->value,
            'animal_product_choice' => $profile->animal_product_choice?->value,
            'calculated_diet_type' => $profile->calculated_diet_type?->value,
            'additional_goals' => $profile->additional_goals,
        ];
    }

    /**
     * @return list<string>
     */
    private function identifyMissingData(UserProfile $profile): array
    {
        $missing = [];

        if ($profile->age === null) {
            $missing[] = 'age';
        }
        if ($profile->height === null) {
            $missing[] = 'height';
        }
        if ($profile->weight === null) {
            $missing[] = 'weight';
        }
        if ($profile->sex === null) {
            $missing[] = 'sex';
        }
        if ($profile->goal_choice === null) {
            $missing[] = 'primary_goal';
        }
        if ($profile->dietaryPreferences->isEmpty()) {
            $missing[] = 'dietary_preferences';
        }

        return $missing;
    }

    /**
     * Format context as a natural language string for AI consumption.
     *
     * @param  array{biometrics: array<string, mixed>, dietary_preferences: array<int, array{name: string, severity: mixed, notes: mixed}>, health_conditions: array<int, array{name: string, notes: mixed}>, medications: array<int, array{name: string, dosage: mixed, frequency: mixed, purpose: mixed}>, goals: array<string, mixed>}  $context
     * @param  list<string>  $missingData
     */
    private function formatContextForAI(array $context, array $missingData): string
    {
        $parts = [];

        // Biometrics
        /** @var array<string, mixed> $bio */
        $bio = $context['biometrics'];
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

        if ($bioParts !== []) {
            $parts[] = 'BIOMETRICS: '.implode(', ', $bioParts);
        }

        // Dietary Preferences
        /** @var array<int, array{name: string, severity: mixed, notes: mixed}> $prefs */
        $prefs = $context['dietary_preferences'];
        if ($prefs !== []) {
            $prefStrings = array_map(function (array $p): string {
                $severity = is_scalar($p['severity']) ? ' ('.$p['severity'].')' : '';
                $notes = is_scalar($p['notes']) && (string) $p['notes'] !== '' ? ': '.$p['notes'] : '';

                return $p['name'].$severity.$notes;
            }, $prefs);
            $parts[] = 'DIETARY PREFERENCES/RESTRICTIONS: '.implode(', ', $prefStrings);
        }

        // Health Conditions
        /** @var array<int, array{name: string, notes: mixed}> $conditions */
        $conditions = $context['health_conditions'];
        if ($conditions !== []) {
            $conditionStrings = array_map(function (array $c): string {
                $notes = is_scalar($c['notes']) && (string) $c['notes'] !== '' ? ' ('.$c['notes'].')' : '';

                return $c['name'].$notes;
            }, $conditions);
            $parts[] = 'HEALTH CONDITIONS: '.implode(', ', $conditionStrings);
        }

        // Medications
        /** @var array<int, array{name: string, dosage: mixed, frequency: mixed, purpose: mixed}> $medications */
        $medications = $context['medications'];
        if ($medications !== []) {
            $medStrings = array_map(function (array $m): string {
                $dosage = is_scalar($m['dosage']) && (string) $m['dosage'] !== '' ? ' '.$m['dosage'] : '';
                $frequency = is_scalar($m['frequency']) && (string) $m['frequency'] !== '' ? ' ('.$m['frequency'].')' : '';

                return $m['name'].$dosage.$frequency;
            }, $medications);
            $parts[] = 'MEDICATIONS: '.implode(', ', $medStrings);
        }

        // Goals
        /** @var array<string, mixed> $goals */
        $goals = $context['goals'];
        $goalParts = [];
        if (isset($goals['primary_goal']) && is_scalar($goals['primary_goal'])) {
            $goalParts[] = 'Primary Goal: '.$goals['primary_goal'];
        }
        if (isset($goals['target_weight_kg']) && is_scalar($goals['target_weight_kg'])) {
            $goalParts[] = 'Target Weight: '.$goals['target_weight_kg'].'kg';
        }
        if (isset($goals['calculated_diet_type']) && is_scalar($goals['calculated_diet_type'])) {
            $parts[] = 'Diet Type: '.$goals['calculated_diet_type'];
            $dietTypeEnum = DietType::tryFrom((string) $goals['calculated_diet_type']);
            if ($dietTypeEnum instanceof DietType) {
                $macros = $dietTypeEnum->macroTargets();
                $parts[] = 'Recommended Macros: '.$macros['carbs'].'% carbs, '.$macros['protein'].'% protein, '.$macros['fat'].'% fat';
            }
        }
        if (isset($goals['additional_goals']) && is_scalar($goals['additional_goals'])) {
            $goalParts[] = 'Additional Goals: '.$goals['additional_goals'];
        }

        if ($goalParts !== []) {
            $parts[] = 'GOALS: '.implode(', ', $goalParts);
        }

        // Missing data note
        if ($missingData !== []) {
            $parts[] = 'MISSING PROFILE DATA: '.implode(', ', $missingData).'. Consider suggesting the user complete their profile for more personalized advice when relevant.';
        }

        return implode("\n", $parts);
    }
}
