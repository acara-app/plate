<?php

declare(strict_types=1);

namespace App\Http\Controllers\Diabetes;

use App\Http\Layouts\DiabetesLayout;
use App\Models\DiabetesLog;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardDiabetesLogController
{
    public function __construct(
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(): Response
    {
        $user = $this->currentUser;

        // Get all logs for visualization (not paginated)
        $allLogs = $user->diabetesLogs()
            ->latest('measured_at')
            ->get();

        return Inertia::render('diabetes-log/tracking', [
            'logs' => $allLogs,
            ...DiabetesLayout::props($user),
        ]);
    }
}
