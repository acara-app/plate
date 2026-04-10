<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\SleepSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $sample_uuid
 * @property CarbonInterface $started_at
 * @property CarbonInterface $ended_at
 * @property string $stage
 * @property string|null $source
 * @property string|null $timezone
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read User $user
 */
final class SleepSession extends Model
{
    /** @use HasFactory<SleepSessionFactory> */
    use HasFactory;

    public const string STAGE_IN_BED = 'inBed';

    public const string STAGE_ASLEEP_CORE = 'asleepCore';

    public const string STAGE_ASLEEP_DEEP = 'asleepDeep';

    public const string STAGE_ASLEEP_REM = 'asleepREM';

    public const string STAGE_AWAKE = 'awake';

    public const string STAGE_ASLEEP_UNSPECIFIED = 'asleepUnspecified';

    protected $fillable = [
        'user_id',
        'sample_uuid',
        'started_at',
        'ended_at',
        'stage',
        'source',
        'timezone',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function durationHours(): float
    {
        return $this->started_at->diffInSeconds($this->ended_at) / 3600.0;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    protected function scopeForNight(Builder $query, CarbonInterface $nightDate, string $timezone = 'UTC'): void
    {
        $nightStart = $nightDate->copy()->setTimezone($timezone)->setTime(12, 0);
        $nightEnd = $nightDate->copy()->setTimezone($timezone)->addDay()->setTime(12, 0);

        $query->where('started_at', '>=', $nightStart->utc())
            ->where('started_at', '<', $nightEnd->utc());
    }
}
