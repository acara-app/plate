<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GlucoseReadingType;
use App\Enums\HealthEntrySource;
use App\Enums\InsulinType;
use Carbon\CarbonInterface;
use Database\Factories\HealthEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read float|null $glucose_value
 * @property-read GlucoseReadingType|null $glucose_reading_type
 * @property-read CarbonInterface $measured_at
 * @property-read string|null $notes
 * @property-read HealthEntrySource|null $source
 * @property-read float|null $insulin_units
 * @property-read InsulinType|null $insulin_type
 * @property-read string|null $medication_name
 * @property-read string|null $medication_dosage
 * @property-read float|null $weight
 * @property-read int|null $blood_pressure_systolic
 * @property-read int|null $blood_pressure_diastolic
 * @property-read float|null $a1c_value
 * @property-read int|null $carbs_grams
 * @property-read string|null $exercise_type
 * @property-read int|null $exercise_duration_minutes
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 */
final class HealthEntry extends Model
{
    /**
     * @use HasFactory<HealthEntryFactory>
     */
    use HasFactory;

    protected $table = 'health_entries';

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'user_id' => 'integer',
            'glucose_value' => 'float',
            'glucose_reading_type' => GlucoseReadingType::class,
            'measured_at' => 'datetime',
            'notes' => 'string',
            'source' => HealthEntrySource::class,
            'insulin_units' => 'float',
            'insulin_type' => InsulinType::class,
            'medication_name' => 'string',
            'medication_dosage' => 'string',
            'weight' => 'float',
            'blood_pressure_systolic' => 'integer',
            'blood_pressure_diastolic' => 'integer',
            'a1c_value' => 'float',
            'carbs_grams' => 'integer',
            'exercise_type' => 'string',
            'exercise_duration_minutes' => 'integer',
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
}
