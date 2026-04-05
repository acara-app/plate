<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HealthSyncSample;

final readonly class DeleteHealthSampleAction
{
    public function handle(HealthSyncSample $sample): void
    {
        if ($sample->group_id !== null) {
            HealthSyncSample::query()
                ->where('group_id', $sample->group_id)
                ->delete();

            return;
        }

        $sample->delete();
    }
}
