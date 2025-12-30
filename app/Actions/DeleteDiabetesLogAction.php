<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DiabetesLog;

final readonly class DeleteDiabetesLogAction
{
    /**
     * Execute the action.
     */
    public function handle(DiabetesLog $diabetesLog): void
    {
        $diabetesLog->delete();
    }
}
