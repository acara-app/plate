<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DiabetesLog;

final readonly class RecordDiabetesLogAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        array $data
    ): DiabetesLog {
        return DiabetesLog::query()->create($data);
    }
}
