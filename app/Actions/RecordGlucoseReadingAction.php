<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\GlucoseReading;

final readonly class RecordGlucoseReadingAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        array $data
    ): GlucoseReading {
        return GlucoseReading::query()->create($data);
    }
}
