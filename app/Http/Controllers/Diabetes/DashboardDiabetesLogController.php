<?php

declare(strict_types=1);

namespace App\Http\Controllers\Diabetes;

use App\Http\Layouts\DiabetesLayout;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardDiabetesLogController
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

        return Inertia::render('diabetes-log/tracking', [
            ...$dashboardData,
            ...DiabetesLayout::props($this->currentUser),
        ]);
    }
}

