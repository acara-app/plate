<?php

declare(strict_types=1);

use App\Http\Controllers\HealthEntry\InsightsHealthEntryController;
use App\Models\User;

covers(InsightsHealthEntryController::class);

it('renders diabetes insights page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('health-entries.insights'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('glucoseAnalysis')
            ->has('concerns')
            ->has('hasMealPlan')
            ->has('mealPlan'));
});
