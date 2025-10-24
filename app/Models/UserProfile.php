<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Sex;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
 * @property-read bool $onboarding_completed
 * @property-read CarbonInterface|null $onboarding_completed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Goal|null $goal
 * @property-read Lifestyle|null $lifestyle
 */
final class UserProfile extends Model
{
    /** @use HasFactory<\Database\Factories\UserProfileFactory> */
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
            'height' => 'float',
            'weight' => 'float',
            'sex' => Sex::class,
            'goal_id' => 'integer',
            'target_weight' => 'float',
            'additional_goals' => 'string',
            'lifestyle_id' => 'integer',
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
        )->withTimestamps();
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

    public function calculateBMI(): ?float
    {
        if ($this->height && $this->weight) {
            $heightInMeters = $this->height / 100;

            return round($this->weight / ($heightInMeters * $heightInMeters), 2);
        }

        return null;
    }

    public function calculateBMR(): ?float
    {
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
    }

    public function calculateTDEE(): ?float
    {
        $bmr = $this->calculateBMR();

        if (! $bmr || ! $this->lifestyle) {
            return null;
        }

        return round($bmr * $this->lifestyle->activity_multiplier, 2);
    }
}
