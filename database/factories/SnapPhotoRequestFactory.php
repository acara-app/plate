<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\FoodAnalysisData;
use App\Models\SnapPhotoPeriod;
use App\Models\SnapPhotoRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SnapPhotoRequest>
 */
final class SnapPhotoRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $guestId = (string) Str::uuid();

        return [
            'period_id' => SnapPhotoPeriod::factory(),
            'subject' => "guest:{$guestId}",
            'request_key' => (string) Str::uuid(),
            'fingerprint' => hash('sha256', $guestId),
            'guest_id' => $guestId,
            'status' => 'pending',
            'attempt' => (string) Str::uuid(),
            'source' => 'public_snap_to_track',
            'result' => null,
            'created_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ];
    }

    public function succeeded(FoodAnalysisData $result): self
    {
        return $this->state(fn (): array => ['status' => 'succeeded', 'result' => $result->toJson()]);
    }
}
