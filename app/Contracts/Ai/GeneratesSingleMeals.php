<?php

declare(strict_types=1);

namespace App\Contracts\Ai;

use App\Ai\Agents\SingleMealAgent;
use App\DataObjects\GeneratedMealData;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;

#[Bind(SingleMealAgent::class)]
interface GeneratesSingleMeals
{
    public function generate(
        User $user,
        string $mealType,
        ?string $cuisine = null,
        ?int $maxCalories = null,
        ?string $specificRequest = null,
    ): GeneratedMealData;
}
