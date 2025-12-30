<?php

declare(strict_types=1);

namespace App\Http\Controllers\Diabetes;

use App\Actions\DeleteDiabetesLogAction;
use App\Models\DiabetesLog;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class DestroyDiabetesLogController
{
    public function __construct(
        private DeleteDiabetesLogAction $deleteDiabetesLog,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(DiabetesLog $diabetesLog): RedirectResponse
    {
        $user = $this->currentUser;

        abort_if($diabetesLog->user_id !== $user->id, 403);

        $this->deleteDiabetesLog->handle($diabetesLog);

        return back()->with('success', 'Diabetes log entry deleted successfully.');
    }
}
