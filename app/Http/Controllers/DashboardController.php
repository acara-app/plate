<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

final class DashboardController
{
    public function show(Request $request): \Inertia\Response
    {
        return Inertia::render('dashboard');
    }
}
