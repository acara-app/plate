<?php

declare(strict_types=1);

namespace App\Actions;

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
     * @return array<int, array<string, mixed>>
     */
    private function getDietaryPreferences(UserProfile $profile): array
    {
        return $profile->dietaryPreferences->map(fn (\App\Models\DietaryPreference $pref): array => [
            'name' => $pref->name,
            'severity' => $pref->pivot->severity ?? null,
            'notes' => $pref->pivot->notes ?? null,
        ])->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getHealthConditions(UserProfile $profile): array
    {
        return $profile->healthConditions->map(fn (\App\Models\HealthCondition $condition): array => [
            'name' => $condition->name,
            'notes' => $condition->pivot->notes ?? null,
        ])->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getMedications(UserProfile $profile): array
    {
        return $profile->medications->map(fn (UserMedication $med): array => [
            'name' => $med->name,
            'dosage' => $med->dosage,
            'frequency' => $med->frequency,
            'purpose' => $med->purpose,
        ])->all();
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
     * @return array<int, string>
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
     * @param  array<string, mixed>  $context
     * @param  array<int, string>  $missingData
     */
    private function formatContextForAI(array $context, array $missingData): string
    {
        $parts = [];

        // Biometrics
        $bio = $context['biometrics'];
        $bioParts = [];
        if ($bio['age'] !== null) {
            $bioParts[] = "Age: {$bio['age']}";
        }
        if ($bio['height_cm'] !== null) {
            $bioParts[] = "Height: {$bio['height_cm']}cm";
        }
        if ($bio['weight_kg'] !== null) {
            $bioParts[] = "Weight: {$bio['weight_kg']}kg";
        }
        if ($bio['sex'] !== null) {
            $bioParts[] = "Sex: {$bio['sex']}";
        }
        if ($bio['bmi'] !== null) {
            $bioParts[] = "BMI: {$bio['bmi']}";
        }
        if ($bio['tdee'] !== null) {
            $bioParts[] = "Daily Calorie Needs (TDEE): {$bio['tdee']} kcal";
        }

        if ($bioParts !== []) {
            $parts[] = 'BIOMETRICS: '.implode(', ', $bioParts);
        }

        // Dietary Preferences
        $prefs = $context['dietary_preferences'];
        if ($prefs !== []) {
            $prefStrings = array_map(fn (array $p): string => $p['name'].($p['severity'] ? " ({$p['severity']})" : '').($p['notes'] ? ": {$p['notes']}" : ''), $prefs);
            $parts[] = 'DIETARY PREFERENCES/RESTRICTIONS: '.implode(', ', $prefStrings);
        }

        // Health Conditions
        $conditions = $context['health_conditions'];
        if ($conditions !== []) {
            $conditionStrings = array_map(fn (array $c): string => $c['name'].($c['notes'] ? " ({$c['notes']})" : ''), $conditions);
            $parts[] = 'HEALTH CONDITIONS: '.implode(', ', $conditionStrings);
        }

        // Medications
        $medications = $context['medications'];
        if ($medications !== []) {
            $medStrings = array_map(fn (array $m): string => "{$m['name']}".($m['dosage'] ? " {$m['dosage']}" : '').($m['frequency'] ? " ({$m['frequency']})" : ''), $medications);
            $parts[] = 'MEDICATIONS: '.implode(', ', $medStrings);
        }

        // Goals
        $goals = $context['goals'];
        $goalParts = [];
        if ($goals['primary_goal'] !== null) {
            $goalParts[] = "Primary Goal: {$goals['primary_goal']}";
        }
        if ($goals['target_weight_kg'] !== null) {
            $goalParts[] = "Target Weight: {$goals['target_weight_kg']}kg";
        }
        if ($goals['calculated_diet_type'] !== null) {
            $goalParts[] = "Diet Type: {$goals['calculated_diet_type']}";
        }
        if ($goals['additional_goals'] !== null) {
            $goalParts[] = "Additional Goals: {$goals['additional_goals']}";
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
