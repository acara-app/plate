<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SnapJourney;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SnapJourney>
 */
final class SnapJourneyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guest_hash' => hash('sha256', (string) Str::uuid()),
            'user_id' => null,
            'device' => 'mobile',
            'acquisition_source' => 'direct',
            'acquisition_medium' => null,
            'acquisition_campaign' => null,
            'internal' => false,
            'started_at' => now(),
            'expires_at' => now()->addDays(config()->integer('snap.journey_days')),
        ];
    }
}
