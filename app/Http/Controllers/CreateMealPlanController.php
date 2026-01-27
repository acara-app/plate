<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DietType;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;

final readonly class CreateMealPlanController
{
    public function __invoke(
        #[CurrentUser] User $user,
    ): \Inertia\Response {
        return Inertia::render('meal-plans/create', [
            'dietTypes' => DietType::toArray(),
            'userProfile' => $user->profile?->toArray(),
        ]);
    }
}
