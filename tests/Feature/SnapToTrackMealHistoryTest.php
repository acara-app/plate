<?php

declare(strict_types=1);

use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

it('stores a reviewed meal at the same instant regardless of its timezone offset', function (string $measuredAt): void {
    $user = User::factory()->create();
    $token = Str::random(64);
    AnalysisDraft::factory()->claimed($user)->create([
        'token_hash' => AnalysisDraft::hashToken($token),
    ]);

    $this->actingAs($user)
        ->post(route('snap-to-track.review.store', ['draft' => $token]), [
            'items' => [['name' => 'Banana', 'calories' => 105, 'carbs' => 27]],
            'measured_at' => $measuredAt,
        ])
        ->assertRedirectToRoute('snap-to-track.index');

    $this->assertDatabaseHas('health_sync_samples', [
        'user_id' => $user->id,
        'type_identifier' => 'dietaryEnergy',
        'value' => 105,
        'measured_at' => '2026-10-16 03:57:00',
    ]);
})->with([
    'Ulaanbaatar' => '2026-10-16T11:57:00+08:00',
    'Los Angeles' => '2026-10-15T20:57:00-07:00',
    'UTC' => '2026-10-16T03:57:00Z',
]);

it('keeps the full reviewed meal available in the logbook after editing it', function (): void {
    $user = User::factory()->create();
    $token = Str::random(64);
    AnalysisDraft::factory()->claimed($user)->create([
        'token_hash' => AnalysisDraft::hashToken($token),
    ]);
    $items = [
        ['name' => 'Banana', 'portion' => '1 medium', 'calories' => 105, 'protein' => 1.3, 'carbs' => 27, 'fat' => 0.3, 'provenance' => 'model'],
        ['name' => 'Yogurt', 'portion' => '100g', 'calories' => 60, 'protein' => 4, 'carbs' => 5, 'fat' => 3, 'provenance' => 'reference'],
    ];

    $this->actingAs($user)->post(route('snap-to-track.review.store', ['draft' => $token]), [
        'items' => $items,
        'measured_at' => '2026-10-16T03:57:00Z',
        'notes' => 'Breakfast',
    ])->assertRedirectToRoute('snap-to-track.index');

    $this->get(route('health-entries.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('logs.data', 1)
        ->has('logs.data.0.food_analysis.items', 2)
        ->where('logs.data.0.food_analysis.items.0.name', 'Banana')
        ->where('logs.data.0.food_analysis.items.0.portion', '1 medium')
        ->where('logs.data.0.food_analysis.confidence', 82));

    $sample = HealthSyncSample::query()->whereBelongsTo($user)->firstOrFail();
    $items[0] = ['name' => 'Large banana', 'portion' => '1 large', 'calories' => 120, 'protein' => 1.5, 'carbs' => 30, 'fat' => 0.4, 'provenance' => 'user'];

    $this->put(route('health-entries.update', $sample), [
        'log_type' => 'food',
        'items' => $items,
        'measured_at' => '2026-10-16T12:15:00+08:00',
        'notes' => 'Updated breakfast',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $this->assertDatabaseHas('health_sync_samples', [
        'user_id' => $user->id,
        'type_identifier' => 'dietaryEnergy',
        'value' => 180,
        'notes' => 'Updated breakfast',
        'measured_at' => '2026-10-16 04:15:00',
    ]);
    $this->get(route('health-entries.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('logs.data', 1)
        ->has('logs.data.0.food_analysis.items', 2)
        ->where('logs.data.0.food_analysis.items.0.name', 'Large banana')
        ->where('logs.data.0.food_analysis.items.0.calories', 120)
        ->where('logs.data.0.food_analysis.items.1.name', 'Yogurt')
        ->where('logs.data.0.food_analysis.confidence', 82)
        ->where('logs.data.0.calories', 180));

    $this->put(route('health-entries.update', $sample), [
        'log_type' => 'food',
        'carbs_grams' => 35,
        'protein_grams' => 5.5,
        'fat_grams' => 3.4,
        'calories' => 180,
        'measured_at' => '2026-10-16T04:15:00Z',
        'notes' => 'Only the notes changed',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $this->get(route('health-entries.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('logs.data.0.food_analysis.items', 2)
        ->where('logs.data.0.food_analysis.items.0.name', 'Large banana')
        ->where('logs.data.0.notes', 'Only the notes changed'));
});

it('leaves a saved meal unchanged when an ingredient edit is invalid', function (array $items, string $error): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->carbohydrates()->fromWeb()->for($user)->create([
        'value' => 27,
        'notes' => 'Original meal',
    ]);

    $this->actingAs($user)->put(route('health-entries.update', $sample), [
        'log_type' => 'food',
        'items' => $items,
        'measured_at' => '2026-10-16T03:57:00Z',
        'notes' => 'Invalid edit',
    ])->assertSessionHasErrors($error);

    $this->assertDatabaseHas('health_sync_samples', [
        'id' => $sample->id,
        'value' => 27,
        'notes' => 'Original meal',
    ]);
    $this->assertDatabaseCount('health_sync_samples', 1);
})->with([
    'empty ingredients' => [[], 'items'],
    'negative calories' => [[['name' => 'Banana', 'calories' => -1]], 'items.0.calories'],
    'combined calorie cap' => [[['name' => 'A', 'calories' => 3000], ['name' => 'B', 'calories' => 3000]], 'items'],
]);
