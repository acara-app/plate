<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DiabetesLog;

final readonly class UpdateDiabetesLogAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(DiabetesLog $diabetesLog, array $data): DiabetesLog
    {
        $diabetesLog->update($data);

        return $diabetesLog->refresh();
    }
}
