<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;

final readonly class CreateMealPlanController
{
    public function __invoke(): \Inertia\Response
    {
        return Inertia::render('meal-plans/create');
    }
}
