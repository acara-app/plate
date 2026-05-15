<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Ai\AiSafeUserProfileData;
use App\Enums\UserProfileAttributeCategory;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserProfileAttribute;
use Illuminate\Database\Eloquent\Collection;

final readonly class BuildAiSafeUserProfile
{
    public function handle(User $user): AiSafeUserProfileData
    {
        $user->loadMissing('profile.attributes');

        $profile = $user->profile instanceof UserProfile
            ? $user->profile
            : $user->profile()->firstOrCreate(['user_id' => $user->id]);

        $profile->loadMissing('attributes');

        /** @var Collection<int, UserProfileAttribute> $attributes */
        $attributes = $profile->attributes;

        return new AiSafeUserProfileData(
            onboardingCompleted: (bool) $profile->onboarding_completed,
            missingData: $this->identifyMissingData($profile, $attributes),
            sections: [
                'biometrics' => $this->getBiometrics($profile),
                'dietary_preferences' => $this->formatDietaryAttributes($attributes),
                'goals' => $this->getGoals($profile),
                'health_conditions' => $this->formatAttributesForCategory($attributes, UserProfileAttributeCategory::HealthCondition),
                'medications' => $this->formatAttributesForCategory($attributes, UserProfileAttributeCategory::Medication),
                'household' => [
                    'summary' => $profile->household_context,
                ],
            ],
        );
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
     * @param  Collection<int, UserProfileAttribute>  $attributes
     * @return array<int, array<string, mixed>>
     */
    private function formatDietaryAttributes(Collection $attributes): array
    {
        return $this->formatAttributes(
            $attributes->filter(fn (UserProfileAttribute $attribute): bool => $this->isDietaryAttribute($attribute)),
        );
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
     * @param  Collection<int, UserProfileAttribute>  $attributes
     * @return array<int, array<string, mixed>>
     */
    private function formatAttributesForCategory(Collection $attributes, UserProfileAttributeCategory $category): array
    {
        return $this->formatAttributes(
            $attributes->filter(
                fn (UserProfileAttribute $attribute): bool => $attribute->category === $category,
            ),
        );
    }

    /**
     * @param  iterable<UserProfileAttribute>  $attributes
     * @return array<int, array<string, mixed>>
     */
    private function formatAttributes(iterable $attributes): array
    {
        $formatted = [];

        foreach ($attributes as $attribute) {
            $formatted[] = [
                'category' => $attribute->category->value,
                'name' => $attribute->value,
                'severity' => $attribute->severity?->value,
                'notes' => $attribute->notes,
                'metadata' => $this->sanitizeMetadata($attribute->metadata),
            ];
        }

        return $formatted;
    }

    /**
     * @return list<string>
     */
    private function identifyMissingData(UserProfile $profile, Collection $attributes): array
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

        if (! $attributes->contains(fn (UserProfileAttribute $attribute): bool => $this->isDietaryAttribute($attribute))) {
            $missing[] = 'dietary_preferences';
        }

        return $missing;
    }

    private function isDietaryAttribute(UserProfileAttribute $attribute): bool
    {
        return ! in_array($attribute->category, [
            UserProfileAttributeCategory::HealthCondition,
            UserProfileAttributeCategory::Medication,
        ], true);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    private function sanitizeMetadata(?array $metadata): ?array
    {
        if ($metadata === null || $metadata === []) {
            return null;
        }

        $sanitized = [];

        foreach ($metadata as $key => $value) {
            $sanitized[$key] = $this->sanitizeMetadataValue($value);
        }

        return $sanitized;
    }

    private function sanitizeMetadataValue(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return null;
        }

        $sanitized = [];

        foreach ($value as $key => $nestedValue) {
            $sanitized[$key] = $this->sanitizeMetadataValue($nestedValue);
        }

        return $sanitized;
    }
}
