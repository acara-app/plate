<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;

final class DashboardController
{
    public function show(): \Inertia\Response
    {
        return Inertia::render('dashboard');
    }
}
