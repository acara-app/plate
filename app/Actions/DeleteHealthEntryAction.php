<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HealthEntry;

final readonly class DeleteHealthEntryAction
{
    /**
     * Execute the action.
     */
    public function handle(HealthEntry $healthEntry): void
    {
        $healthEntry->delete();
    }
}
