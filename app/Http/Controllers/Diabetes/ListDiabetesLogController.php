<?php

declare(strict_types=1);

namespace App\Http\Controllers\Diabetes;

use App\Actions\GetUserDiabetesLogsAction;
use App\Http\Layouts\DiabetesLayout;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ListDiabetesLogController
{
    public function __construct(
        private GetUserDiabetesLogsAction $getUserDiabetesLogs,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('diabetes-log/index', [
            'logs' => Inertia::scroll(fn () => $this->getUserDiabetesLogs->handle($this->currentUser)),
            ...DiabetesLayout::props($this->currentUser),
        ]);
    }
}
