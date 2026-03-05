<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AllergySeverity;
use App\Enums\UserProfileAttributeCategory;
use App\Models\UserProfile;
use App\Models\UserProfileAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfileAttribute>
 */
final class UserProfileAttributeFactory extends Factory
{
    protected $model = UserProfileAttribute::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var UserProfileAttributeCategory $category */
        $category = fake()->randomElement(UserProfileAttributeCategory::cases());

        return [
            'user_profile_id' => UserProfile::factory(),
            'category' => $category->value,
            'value' => fake()->word(),
        ];
    }

    public function allergy(string $name = 'Peanuts', AllergySeverity $severity = AllergySeverity::Moderate): static
    {
        return $this->state([
            'category' => UserProfileAttributeCategory::Allergy,
            'value' => $name,
            'severity' => $severity,
        ]);
    }

    public function healthCondition(string $name = 'Type 2 Diabetes'): static
    {
        return $this->state([
            'category' => UserProfileAttributeCategory::HealthCondition,
            'value' => $name,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function medication(string $name = 'Metformin', ?array $metadata = null): static
    {
        return $this->state([
            'category' => UserProfileAttributeCategory::Medication,
            'value' => $name,
            'metadata' => $metadata ?? [
                'dosage' => '500mg',
                'frequency' => 'twice daily',
                'purpose' => 'Blood sugar control',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function withMetadata(?array $metadata): static
    {
        return $this->state(fn (): array => ['metadata' => $metadata]);
    }

    public function dietaryPattern(string $name = 'Vegan'): static
    {
        return $this->state([
            'category' => UserProfileAttributeCategory::DietaryPattern,
            'value' => $name,
        ]);
    }

    public function restriction(string $name = 'Halal'): static
    {
        return $this->state([
            'category' => UserProfileAttributeCategory::Restriction,
            'value' => $name,
        ]);
    }
}
