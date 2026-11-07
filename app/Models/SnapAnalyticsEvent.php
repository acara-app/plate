<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SnapAnalyticsEventFactory;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string|null $journey_id
 * @property string $event_key
 * @property string $name
 * @property array<string, bool|int|string|null> $properties
 * @property CarbonImmutable $created_at
 * @property-read SnapJourney|null $journey
 */
#[WithoutTimestamps]
final class SnapAnalyticsEvent extends Model
{
    /** @use HasFactory<SnapAnalyticsEventFactory> */
    use HasFactory;

    /** @return BelongsTo<SnapJourney, $this> */
    public function journey(): BelongsTo
    {
        return $this->belongsTo(SnapJourney::class, 'journey_id');
    }

    /** @return array<string, string> */
    public function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
