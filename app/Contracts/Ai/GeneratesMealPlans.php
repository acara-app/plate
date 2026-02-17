<?php

declare(strict_types=1);

namespace App\Contracts\Ai;

use App\Ai\Agents\MealPlanAgent;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;

#[Bind(MealPlanAgent::class)]
interface GeneratesMealPlans
{
    public function handle(User $user, int $totalDays = 7): void;
}
