<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\UserProfileHealthConditionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property-read int $id
 * @property-read int $user_profile_id
 * @property-read int $health_condition_id
 * @property-read string|null $notes
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read UserProfile $userProfile
 * @property-read HealthCondition $healthCondition
 */
final class UserProfileHealthCondition extends Pivot
{
    /** @use HasFactory<UserProfileHealthConditionFactory> */
    use HasFactory;

    protected $table = 'user_profile_health_condition';

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'user_profile_id' => 'integer',
            'health_condition_id' => 'integer',
            'notes' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<UserProfile, $this>
     */
    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class);
    }

    /**
     * @return BelongsTo<HealthCondition, $this>
     */
    public function healthCondition(): BelongsTo
    {
        return $this->belongsTo(HealthCondition::class);
    }
}
