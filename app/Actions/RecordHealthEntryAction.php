<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthEntrySource;
use App\Models\HealthEntry;

final readonly class RecordHealthEntryAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?HealthEntrySource $source = null): HealthEntry
    {
        if ($source instanceof HealthEntrySource) {
            $data['source'] = $source;
        }

        return HealthEntry::query()->create($data);
    }
}
