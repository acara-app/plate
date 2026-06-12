<?php

declare(strict_types=1);

use App\Models\HealthSyncSample;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

it('renders assembled health entries, not raw samples, on the index', function (): void {
    $user = User::factory()->create();

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'value' => 120,
        'measured_at' => now(),
    ]);

    HealthSyncSample::factory()->carbohydrates()->create([
        'user_id' => $user->id,
        'value' => 45,
        'group_id' => 'meal-1',
        'measured_at' => now()->subHour(),
    ]);

    actingAs($user)
        ->get(route('health-entries.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('health-entries/index')
            ->has('logs.data', 2)
            ->where('logs.data.0.glucose_value', 120)
            ->where('logs.data.1.carbs_grams', 45)
        );
});

it("merges a food meal's macro samples into a single assembled entry", function (): void {
    $user = User::factory()->create();

    HealthSyncSample::factory()->carbohydrates()->create(['user_id' => $user->id, 'value' => 45, 'group_id' => 'meal-1', 'measured_at' => now()]);
    HealthSyncSample::factory()->create(['user_id' => $user->id, 'type_identifier' => 'dietaryEnergy', 'value' => 600, 'unit' => 'kcal', 'group_id' => 'meal-1', 'measured_at' => now()]);

    actingAs($user)
        ->get(route('health-entries.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.carbs_grams', 45)
            ->where('logs.data.0.calories', 600)
        );
});
