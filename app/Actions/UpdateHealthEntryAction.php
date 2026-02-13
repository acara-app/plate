<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HealthEntry;

final readonly class UpdateHealthEntryAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(HealthEntry $healthEntry, array $data): HealthEntry
    {
        $healthEntry->update($data);

        return $healthEntry->refresh();
    }
}
