<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\HealthEntry;

use App\Http\Layouts\DiabetesLayout;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class ListHealthEntrySuggestionsController
{
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return response()->json([
            'recent_medications' => DiabetesLayout::getRecentMedications($user),
            'recent_insulins' => DiabetesLayout::getRecentInsulins($user),
            'todays_meals' => DiabetesLayout::getTodaysMeals($user),
        ]);
    }
}
