<?php

declare(strict_types=1);

use App\Models\User;

it('renders diabetes insights page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.insights'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('glucoseAnalysis')
            ->has('concerns')
            ->has('hasMealPlan')
            ->has('mealPlan'));
});
