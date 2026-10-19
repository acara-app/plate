<?php

declare(strict_types=1);

use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

function snapHistoryMealSample(User $user): HealthSyncSample
{
    return HealthSyncSample::query()->whereBelongsTo($user)->oldest('id')->firstOrFail();
}

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

    $this->get(route('health-entries.show', snapHistoryMealSample($user)))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('entry.food_analysis.items', 2)
        ->where('entry.food_analysis.items.0.name', 'Banana')
        ->where('entry.food_analysis.items.0.portion', '1 medium')
        ->where('entry.food_analysis.confidence', 82));

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
    $this->get(route('health-entries.show', snapHistoryMealSample($user)))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('entry.food_analysis.items', 2)
        ->where('entry.food_analysis.items.0.name', 'Large banana')
        ->where('entry.food_analysis.items.0.calories', 120)
        ->where('entry.food_analysis.items.1.name', 'Yogurt')
        ->where('entry.food_analysis.confidence', 82)
        ->where('entry.calories', 180));

    $this->put(route('health-entries.update', $sample), [
        'log_type' => 'food',
        'carbs_grams' => 35,
        'protein_grams' => 5.5,
        'fat_grams' => 3.4,
        'calories' => 180,
        'measured_at' => '2026-10-16T04:15:00Z',
        'notes' => 'Only the notes changed',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $this->get(route('health-entries.show', snapHistoryMealSample($user)))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('entry.food_analysis.items', 2)
        ->where('entry.food_analysis.items.0.name', 'Large banana')
        ->where('entry.notes', 'Only the notes changed'));
});

it('saves a renamed item with its updated nutrition, portion and weight from the logbook', function (): void {
    $user = User::factory()->create();
    $token = Str::random(64);
    AnalysisDraft::factory()->claimed($user)->create([
        'token_hash' => AnalysisDraft::hashToken($token),
    ]);
    $items = [
        ['name' => 'Black olives', 'portion' => '35 g (about 10 olives)', 'grams' => 35, 'calories' => 40, 'protein' => 0.3, 'carbs' => 2.2, 'fat' => 3.8, 'provenance' => 'model'],
        ['name' => 'Lettuce', 'portion' => '50g', 'calories' => 8, 'protein' => 0.6, 'carbs' => 1.5, 'fat' => 0.1, 'provenance' => 'model'],
    ];

    $this->actingAs($user)->post(route('snap-to-track.review.store', ['draft' => $token]), [
        'items' => $items,
        'measured_at' => '2026-10-16T03:57:00Z',
    ])->assertRedirectToRoute('snap-to-track.index');

    $this->get(route('health-entries.show', snapHistoryMealSample($user)))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('entry.food_analysis.items.0.grams', 35)
        ->where('entry.food_analysis.items.1.grams', 50));

    $sample = HealthSyncSample::query()->whereBelongsTo($user)->firstOrFail();
    $items[0] = ['name' => 'Blueberries', 'portion' => '35 g (about ¼ cup)', 'grams' => 35, 'calories' => 20, 'protein' => 0.3, 'carbs' => 5.1, 'fat' => 0.1, 'provenance' => 'user'];

    $this->put(route('health-entries.update', $sample), [
        'log_type' => 'food',
        'items' => $items,
        'measured_at' => '2026-10-16T03:57:00Z',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $this->get(route('health-entries.show', snapHistoryMealSample($user)))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('entry.food_analysis.items.0.name', 'Blueberries')
        ->where('entry.food_analysis.items.0.portion', '35 g (about ¼ cup)')
        ->where('entry.food_analysis.items.0.grams', 35)
        ->where('entry.food_analysis.items.0.calories', 20)
        ->where('entry.food_analysis.items.0.fat', 0.1)
        ->where('entry.food_analysis.items.1.grams', 50)
        ->where('entry.calories', 28));
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
    'excessive weight' => [[['name' => 'Banana', 'grams' => 2001]], 'items.0.grams'],
    'combined calorie cap' => [[['name' => 'A', 'calories' => 3000], ['name' => 'B', 'calories' => 3000]], 'items'],
]);
