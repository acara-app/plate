<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;

final class FoodLogController
{
    public function create(): \Inertia\Response
    {
        return Inertia::render('ongoing-tracking/create-food-log');
    }
}
