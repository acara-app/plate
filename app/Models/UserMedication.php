<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserMedicationFactory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $user_profile_id
 * @property-read string $name
 * @property-read string|null $dosage
 * @property-read string|null $frequency
 * @property-read string|null $purpose
 * @property-read CarbonInterface|null $started_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read UserProfile $userProfile
 */
final class UserMedication extends Model
{
    /** @use HasFactory<UserMedicationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'user_profile_id' => 'integer',
            'name' => 'string',
            'dosage' => 'string',
            'frequency' => 'string',
            'purpose' => 'string',
            'started_at' => 'date',
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
}
