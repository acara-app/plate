<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AllergySeverity;
use App\Enums\UserProfileAttributeCategory;
use Carbon\CarbonInterface;
use Database\Factories\UserProfileAttributeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $user_profile_id
 * @property-read UserProfileAttributeCategory $category
 * @property-read string $value
 * @property-read AllergySeverity|null $severity
 * @property-read string|null $notes
 * @property-read array<string, mixed>|null $metadata
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read UserProfile $userProfile
 */
final class UserProfileAttribute extends Model
{
    /** @use HasFactory<UserProfileAttributeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'user_profile_id' => 'integer',
            'category' => UserProfileAttributeCategory::class,
            'severity' => AllergySeverity::class,
            'metadata' => 'array',
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

    public function getMedicationDosage(): ?string
    {
        return $this->getMetadataString('dosage');
    }

    public function getMedicationFrequency(): ?string
    {
        return $this->getMetadataString('frequency');
    }

    public function getMedicationPurpose(): ?string
    {
        return $this->getMetadataString('purpose');
    }

    private function getMetadataString(string $key): ?string
    {
        $value = $this->metadata[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
