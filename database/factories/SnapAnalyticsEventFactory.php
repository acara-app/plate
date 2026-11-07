<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SnapAnalyticsEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SnapAnalyticsEvent>
 */
final class SnapAnalyticsEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_key' => 'scan:'.Str::uuid(),
            'name' => 'snap_to_track_scan_completed',
            'properties' => ['mode' => 'trial', 'source' => 'public_snap_to_track'],
            'created_at' => now(),
        ];
    }
}
