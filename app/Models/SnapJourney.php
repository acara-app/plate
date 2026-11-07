<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SnapJourneyFactory;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string|null $guest_hash
 * @property int|null $user_id
 * @property string $device
 * @property string|null $acquisition_source
 * @property string|null $acquisition_medium
 * @property string|null $acquisition_campaign
 * @property bool $internal
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable $expires_at
 * @property-read Collection<int, SnapAnalyticsEvent> $events
 */
#[WithoutTimestamps]
final class SnapJourney extends Model
{
    /** @use HasFactory<SnapJourneyFactory> */
    use HasFactory;

    use HasUuids;

    /** @return HasMany<SnapAnalyticsEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(SnapAnalyticsEvent::class, 'journey_id');
    }

    /** @return array<string, string> */
    public function casts(): array
    {
        return [
            'user_id' => 'integer',
            'internal' => 'boolean',
            'started_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
