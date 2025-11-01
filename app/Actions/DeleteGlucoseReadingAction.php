<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\GlucoseReading;

final readonly class DeleteGlucoseReadingAction
{
    /**
     * Execute the action.
     */
    public function handle(GlucoseReading $glucoseReading): void
    {
        $glucoseReading->delete();
    }
}
