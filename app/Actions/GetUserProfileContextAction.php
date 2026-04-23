<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\Actions\GetsUserProfileContext;
use App\Enums\DietType;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserProfileAttribute;

final readonly class GetUserProfileContextAction implements GetsUserProfileContext
{
    /**
     * @return array<string, mixed>
     */
    public function handle(User $user): array
    {
        $profile = $user->profile instanceof UserProfile
            ? $user->profile
            : $user->profile()->firstOrCreate(['user_id' => $user->id]);

        $context = [
            'onboarding_completed' => $profile->onboarding_completed,
            'biometrics' => $this->getBiometrics($profile),
            'dietary_preferences' => $this->getDietaryPreferences($profile),
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
     * @return array<int, array{name: string, severity: string|null, notes: string|null}>
     */
    private function getDietaryPreferences(UserProfile $profile): array
    {
        return array_values($profile->dietaryAttributes->map(fn (UserProfileAttribute $attr): array => [
            'name' => $attr->value,
            'severity' => $attr->severity?->value,
            'notes' => $attr->notes,
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

        if ($profile->dietaryAttributes->isEmpty()) {
            $missing[] = 'dietary_preferences';
        }

        return $missing;
    }

    /**
     * @param  array{biometrics: array<string, mixed>, dietary_preferences: array<int, array{name: string, severity: mixed, notes: mixed}>, goals: array<string, mixed>}  $context
     * @param  list<string>  $missingData
     */
    private function formatContextForAI(array $context, array $missingData): string
    {
        $parts = [];

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

        if ($goalParts !== []) {
            $parts[] = 'GOALS: '.implode(', ', $goalParts);
        }

        if ($missingData !== []) {
            $fieldsList = implode(', ', $missingData);
            $parts[] = sprintf('MISSING PROFILE DATA: %s. Proceed with reasonable defaults — do NOT block the user or ask them to complete their profile first. After fulfilling their request, briefly mention that providing these details (via conversation) would allow more personalized recommendations. Use the update_user_biometrics tool if the user shares this information.', $fieldsList);
        }

        return implode("\n", $parts);
    }
}
