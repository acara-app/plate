<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Http\Layouts\DiabetesLayout;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardHealthEntryController
{
    public function __construct(
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(Request $request): Response
    {
        $timePeriod = $request->query('period', '30d');

        $dashboardData = DiabetesLayout::dashboardData(
            $this->currentUser,
            is_string($timePeriod) ? $timePeriod : '30d'
        );

        return Inertia::render('health-entries/tracking', [
            ...$dashboardData,
            ...DiabetesLayout::props($this->currentUser),
        ]);
    }
}
