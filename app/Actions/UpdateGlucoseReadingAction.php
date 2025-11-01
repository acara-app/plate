<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\GlucoseReading;

final readonly class UpdateGlucoseReadingAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(GlucoseReading $glucoseReading, array $data): GlucoseReading
    {
        $glucoseReading->update($data);

        return $glucoseReading->refresh();
    }
}
