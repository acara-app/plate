<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnimalProductChoice;
use App\Enums\BloodType;
use App\Enums\DietType;
use App\Enums\GlucoseUnit;
use App\Enums\GoalChoice;
use App\Enums\IntensityChoice;
use App\Enums\Sex;
use App\Enums\UserProfileAttributeCategory;
use Carbon\CarbonInterface;
use Database\Factories\UserProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int|null $age
 * @property-read CarbonInterface|null $date_of_birth
 * @property-read float|null $height
 * @property-read float|null $weight
 * @property-read Sex|null $sex
 * @property-read BloodType|null $blood_type
 * @property-read GoalChoice|null $goal_choice
 * @property-read AnimalProductChoice|null $animal_product_choice
 * @property-read IntensityChoice|null $intensity_choice
 * @property-read DietType|null $calculated_diet_type
 * @property-read float|null $derived_activity_multiplier
 * @property-read bool $needs_re_onboarding
 * @property-read float|null $target_weight
 * @property-read string|null $additional_goals
 * @property-read string|null $household_context
 * @property-read GlucoseUnit|null $units_preference
 * @property-read bool $onboarding_completed
 * @property-read CarbonInterface|null $onboarding_completed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 * @property-read float|null $bmi
 * @property-read float|null $bmr
 * @property-read float|null $tdee
 * @property-read Collection<int, UserProfileAttribute> $attributes
 */
#[Appends([
    'bmi',
    'bmr',
    'tdee',
])]
final class UserProfile extends Model
{
    /** @use HasFactory<UserProfileFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'age' => 'integer',
            'date_of_birth' => 'date',
            'height' => 'float',
            'weight' => 'float',
            'sex' => Sex::class,
            'blood_type' => BloodType::class,
            'goal_choice' => GoalChoice::class,
            'animal_product_choice' => AnimalProductChoice::class,
            'intensity_choice' => IntensityChoice::class,
            'calculated_diet_type' => DietType::class,
            'derived_activity_multiplier' => 'float',
            'needs_re_onboarding' => 'boolean',
            'target_weight' => 'float',
            'additional_goals' => 'string',
            'units_preference' => GlucoseUnit::class,
            'onboarding_completed' => 'boolean',
            'onboarding_completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<UserProfileAttribute, $this>
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(UserProfileAttribute::class);
    }

    /**
     * @return HasMany<UserProfileAttribute, $this>
     */
    public function dietaryAttributes(): HasMany
    {
        return $this->attributes()->whereNot('category', UserProfileAttributeCategory::HealthCondition)
            ->whereNot('category', UserProfileAttributeCategory::Medication);
    }

    /**
     * @return HasMany<UserProfileAttribute, $this>
     */
    public function healthConditionAttributes(): HasMany
    {
        return $this->attributes()->where('category', UserProfileAttributeCategory::HealthCondition);
    }

    /**
     * @return HasMany<UserProfileAttribute, $this>
     */
    public function medicationAttributes(): HasMany
    {
        return $this->attributes()->where('category', UserProfileAttributeCategory::Medication);
    }

    /**
     * @return Attribute<float|null, never>
     */
    protected function bmi(): Attribute
    {
        return Attribute::get(function (): ?float {
            if ($this->height && $this->weight) {
                $heightInMeters = $this->height / 100;

                return round($this->weight / ($heightInMeters * $heightInMeters), 2);
            }

            return null;
        });
    }

    /**
     * @return Attribute<float|null, never>
     */
    protected function bmr(): Attribute
    {
        return Attribute::get(function (): ?float {
            if (! $this->weight || ! $this->height || ! $this->age || ! $this->sex) {
                return null;
            }

            $bmr = (10 * $this->weight) + (6.25 * $this->height) - (5 * $this->age);

            if ($this->sex === Sex::Male) {
                $bmr += 5;
            } elseif ($this->sex === Sex::Female) {
                $bmr -= 161;
            }

            return round($bmr, 2);
        });
    }

    /**
     * @return Attribute<float|null, never>
     */
    protected function tdee(): Attribute
    {
        return Attribute::get(function (): ?float {
            if (! $this->bmr) {
                return null;
            }

            $multiplier = $this->derived_activity_multiplier ?? 1.3;

            return round($this->bmr * $multiplier, 2);
        });
    }
}
