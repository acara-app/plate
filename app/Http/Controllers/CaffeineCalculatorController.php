<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CaffeineCalculatorController
{
    public function create(Request $request): Response
    {

        return Inertia::render('caffeine-calculator', [
        ]);
    }
}
