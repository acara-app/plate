<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GlucoseUnit;
use App\Enums\Sex;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int|null $age
 * @property-read float|null $height
 * @property-read float|null $weight
 * @property-read Sex|null $sex
 * @property-read int|null $goal_id
 * @property-read float|null $target_weight
 * @property-read string|null $additional_goals
 * @property-read int|null $lifestyle_id
 * @property-read GlucoseUnit|null $units_preference
 * @property-read bool $onboarding_completed
 * @property-read CarbonInterface|null $onboarding_completed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Goal|null $goal
 * @property-read Lifestyle|null $lifestyle
 * @property-read float|null $bmi
 * @property-read float|null $bmr
 * @property-read float|null $tdee
 * @property-read Collection<int, UserMedication> $medications
 */
final class UserProfile extends Model
{
    /** @use HasFactory<\Database\Factories\UserProfileFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = [
        'bmi',
        'bmr',
        'tdee',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'age' => 'integer',
            'height' => 'float',
            'weight' => 'float',
            'sex' => Sex::class,
            'goal_id' => 'integer',
            'target_weight' => 'float',
            'additional_goals' => 'string',
            'lifestyle_id' => 'integer',
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
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * @return BelongsTo<Lifestyle, $this>
     */
    public function lifestyle(): BelongsTo
    {
        return $this->belongsTo(Lifestyle::class);
    }

    /**
     * @return BelongsToMany<DietaryPreference, $this>
     */
    public function dietaryPreferences(): BelongsToMany
    {
        return $this->belongsToMany(
            DietaryPreference::class,
            'user_profile_dietary_preference'
        )->withPivot(['severity', 'notes'])->withTimestamps();
    }

    /**
     * @return HasMany<UserMedication, $this>
     */
    public function medications(): HasMany
    {
        return $this->hasMany(UserMedication::class);
    }

    /**
     * @return BelongsToMany<HealthCondition, $this, UserProfileHealthCondition>
     */
    public function healthConditions(): BelongsToMany
    {
        return $this->belongsToMany(
            HealthCondition::class,
            'user_profile_health_condition'
        )->using(UserProfileHealthCondition::class)->withPivot('notes')->withTimestamps();
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

            // Mifflin-St Jeor Equation
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
            if (! $this->bmr || ! $this->lifestyle) {
                return null;
            }

            return round($this->bmr * $this->lifestyle->activity_multiplier, 2);
        });
    }
}
