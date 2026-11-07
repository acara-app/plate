<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PhotoAnalysisStatus;
use App\Models\PhotoAnalysisRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PhotoAnalysisRequest>
 */
final class PhotoAnalysisRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $guestId = (string) Str::uuid();
        $image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        return [
            'request_id' => (string) Str::uuid(),
            'guest_id' => $guestId,
            'guest_hash' => hash('sha256', $guestId),
            'fingerprint' => hash('sha256', $image),
            'source' => 'public_snap_to_track',
            'mime_type' => 'image/png',
            'image_base64' => $image,
            'status' => PhotoAnalysisStatus::Queued,
            'deadline_at' => now()->addMinutes(10),
            'expires_at' => now()->addMinutes(PhotoAnalysisRequest::TTL_MINUTES),
        ];
    }
}
