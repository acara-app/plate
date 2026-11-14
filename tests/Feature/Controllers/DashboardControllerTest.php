<?php

declare(strict_types=1);

use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Http\Controllers\DashboardController;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

covers(DashboardController::class);

it('requires authentication', function (): void {
    $response = $this->get(route('dashboard'));

    $response->assertRedirectToRoute('login');
});

it('requires verified email', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->get(route('dashboard'));

    $response->assertRedirectToRoute('verification.notice');
});

it('renders dashboard page for authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard'));
});

it('shows today’s meals and calorie goal on the dashboard', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 17:00:00', 'UTC'));
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
    UserProfile::factory()->for($user)->create(['age' => 30, 'date_of_birth' => null, 'height' => 180, 'weight' => 80, 'sex' => Sex::Male, 'goal_choice' => GoalChoice::HealthyEating, 'derived_activity_multiplier' => 1.5]);

    foreach (['2026-10-18 15:00:00' => 900, '2026-10-18 16:30:00' => 606] as $measuredAt => $calories) {
        HealthSyncSample::factory()->fromWeb()->create(['user_id' => $user->id, 'group_id' => (string) Str::uuid(), 'type_identifier' => 'dietaryEnergy', 'value' => $calories, 'measured_at' => $measuredAt, 'metadata' => ['meal_name' => 'Chicken quinoa salad']]);
    }

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('entries', 1)
            ->where('entries.0.calories', 606)
            ->where('entries.0.meal_name', 'Chicken quinoa salad')
            ->where('goals.calories', 2370));
});
