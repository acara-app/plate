<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function show(): Response
    {
        return Inertia::render('dashboard');
    }
}
