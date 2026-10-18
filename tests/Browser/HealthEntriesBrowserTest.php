<?php

declare(strict_types=1);

use App\Ai\Agents\FoodItemEstimatorAgent;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\TierEntitlement;
use App\Enums\SubscriptionTier;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\MealPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function createLogbookMeal(User $user, bool $large = false): HealthSyncSample
{
    $payload = AnalysisDraft::factory()->make()->payload;
    if ($large) {
        $payload['items'] = array_map(fn (string $name): array => [
            'name' => $name, 'portion' => '35 g', 'grams' => 35,
            'calories' => 20, 'protein' => 1, 'carbs' => 3, 'fat' => 1, 'provenance' => 'model',
        ], ['Mixed salad greens with cucumber, baby spinach and fresh herbs', 'Cherry tomatoes', 'Red onion', 'Shredded carrot', 'Green olives', 'Blueberries', 'Olive oil']);
    }
    $group = Str::uuid()->toString();
    $samples = [];

    foreach (['carbohydrates' => 'carbs', 'protein' => 'protein', 'totalFat' => 'fat', 'dietaryEnergy' => 'calories'] as $type => $field) {
        $samples[] = HealthSyncSample::factory()->fromWeb()->create([
            'user_id' => $user->id,
            'group_id' => $group,
            'type_identifier' => $type,
            'value' => array_sum(array_column($payload['items'], $field)),
            'measured_at' => '2026-10-16T03:57:00Z',
            'notes' => null,
            'metadata' => ['snap_to_track' => $payload],
        ]);
    }

    return $samples[0];
}

it('opens a meal photo with its nutrition and returns to the timeline on mobile', function (): void {
    Storage::fake(MealPhoto::DISK);
    $user = User::factory()->create();
    $sample = createLogbookMeal($user);
    $photo = MealPhoto::factory()->create(['user_id' => $user->id, 'health_group_id' => $sample->group_id]);
    Storage::disk(MealPhoto::DISK)->put($photo->path, (string) UploadedFile::fake()->image('meal.jpg', 960, 640)->get());
    $this->actingAs($user);

    $page = visit(route('health-entries.index', absolute: false))->on()->mobile()
        ->assertSee('Logbook')
        ->click('button[aria-label="View meal photo"]')
        ->assertVisible('[role="dialog"]')
        ->assertSeeIn('[role="dialog"]', '490')
        ->assertVisible('[role="dialog"] img')
        ->keys('[role="dialog"]', 'Escape')
        ->assertMissing('[role="dialog"]')
        ->assertVisible('button[aria-label="View meal photo"]')
        ->assertNoJavaScriptErrors();

    expect($page->script('document.activeElement.getAttribute("aria-label")'))->toBe('View meal photo');
});

it('finds older meals and resets the loaded timeline when switching filters', function (): void {
    $user = User::factory()->create();
    createLogbookMeal($user);
    HealthSyncSample::factory()->count(20)->create([
        'user_id' => $user->id,
        'type_identifier' => 'exerciseMinutes',
        'group_id' => null,
        'measured_at' => '2026-10-17T03:57:00Z',
    ]);
    $this->actingAs($user);

    $page = visit(route('health-entries.index', absolute: false))
        ->assertVisible('#health-entry-list > li:first-child article[aria-label="Exercise"]')
        ->click('Food')
        ->assertSee('Grilled chicken breast')
        ->assertMissing('article[aria-label="Exercise"]')
        ->assertCount('#health-entry-list > li', 1)
        ->click('All')
        ->assertVisible('#health-entry-list > li:first-child article[aria-label="Exercise"]')
        ->assertDontSee('Grilled chicken breast')
        ->assertCount('#health-entry-list h2', 1);

    $page->script('document.querySelector("#health-entry-list > li:last-child").scrollIntoView()');
    $page->assertSee('Grilled chicken breast')
        ->assertCount('#health-entry-list > li', 21)
        ->assertCount('#health-entry-list h2', 2)
        ->assertNoJavaScriptErrors();
});

it('updates a renamed food and saves every ingredient while nutrition details stay collapsed', function (): void {
    $user = User::factory()->create();
    createLogbookMeal($user);
    FoodItemEstimatorAgent::fake([[
        'calories' => 240, 'protein' => 26, 'carbs' => 8, 'fat' => 13,
        'grams' => 170, 'portion' => '170 g (1 block)',
    ]]);
    $this->actingAs($user);

    visit(route('health-entries.index', absolute: false))

        ->click('button[aria-label="Edit log entry"]')
        ->assertMissing('input[name="items[0][calories]"]:visible')
        ->fill('input[name="items[0][name]"]', 'Baked tofu')
        ->click('Update nutrition for “Baked tofu”')
        ->assertValue('input[name="items[0][portion]"]', '170 g (1 block)')
        ->assertSeeIn('[aria-label="Meal totals"]', '450')

        ->press('Save changes')
        ->assertMissing('[role="dialog"]')
        ->assertSee('Baked tofu')
        ->click('Meal details')
        ->assertSee('170 g (1 block)')
        ->assertSee('Steamed rice')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('health_sync_samples', [
        'user_id' => $user->id,
        'type_identifier' => 'dietaryEnergy',
        'value' => 450,
    ]);
    FoodItemEstimatorAgent::assertPromptedTimes(1);
});

it('retains meal edits when nutrition estimation fails', function (bool $creditLimit): void {
    $user = User::factory()->create();
    createLogbookMeal($user);
    app()->instance(ResolvesUserTier::class, new readonly class implements ResolvesUserTier
    {
        public function resolve(User $user): TierEntitlement
        {
            return new TierEntitlement(tier: SubscriptionTier::Free);
        }
    });
    Exceptions::fake();
    FoodItemEstimatorAgent::fake(function (): void {
        throw new RuntimeException('Estimator unavailable');
    });

    if ($creditLimit) {
        config()->set('plate.tier_limits.free', [
            'rolling' => ['limit' => 0.0, 'period_hours' => 24],
            'weekly' => ['limit' => 0.0, 'period_days' => 7],
        ]);
    }

    $this->actingAs($user);
    visit(route('health-entries.index', absolute: false))
        ->click('button[aria-label="Edit log entry"]')
        ->fill('input[name="items[0][name]"]', 'Baked tofu')
        ->click('Update nutrition for “Baked tofu”')
        ->assertSee($creditLimit
            ? "You've reached your AI credit limit. Please adjust the numbers by hand for now."
            : "Couldn't update the nutrition. Please adjust the numbers.")
        ->assertValue('input[name="items[0][name]"]', 'Baked tofu')
        ->assertValue('input[name="items[0][calories]"]', '280')
        ->assertVisible('[role="dialog"]')
        ->assertNoJavaScriptErrors();

    if ($creditLimit) {
        FoodItemEstimatorAgent::assertNeverPrompted();
        Exceptions::assertReported(UsageLimitExceededException::class);
    } else {
        Exceptions::assertReported(RuntimeException::class);
    }
})->with(['provider failure' => [false], 'credit limit' => [true]]);

it('keeps save within reach for seven foods and reveals invalid hidden nutrition fields', function (): void {
    $user = User::factory()->create();
    createLogbookMeal($user, large: true);
    $this->actingAs($user);

    $page = visit(route('health-entries.index', absolute: false))->on()->mobile()
        ->click('button[aria-label="Edit log entry"]')
        ->assertSee('Food 7');

    expect($page->script('(() => { const r = document.querySelector("button[type=submit]").getBoundingClientRect(); return r.top >= 0 && r.bottom <= innerHeight; })()'))->toBeTrue();

    $page->click('fieldset:nth-of-type(7) summary')
        ->fill('input[name="items[6][calories]"]', '-1')
        ->click('fieldset:nth-of-type(7) summary')
        ->press('Save changes')
        ->assertVisible('input[name="items[6][calories]"]')
        ->assertVisible('[role="dialog"]')
        ->press('Cancel')
        ->click('button[aria-label="Edit log entry"]')
        ->assertValue('input[name="items[6][calories]"]', '20')
        ->assertNoJavaScriptErrors();
});

it('adds a manual vital sign and confirms before deleting it', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    visit(route('health-entries.index', absolute: false))
        ->press('Add entry')
        ->click('[role="dialog"] button:has-text("Vitals")')
        ->fill('blood_pressure_diastolic', '80')
        ->press('Save')
        ->assertMissing('[role="dialog"]')
        ->assertVisible('article[aria-label="Vitals"]')
        ->click('button[aria-label="More entry actions"]')
        ->click('Delete entry')
        ->assertVisible('[role="alertdialog"]')
        ->press('Cancel')
        ->assertVisible('article[aria-label="Vitals"]')
        ->click('button[aria-label="More entry actions"]')
        ->click('Delete entry')
        ->press('Delete entry')
        ->assertSee('Your logbook starts here')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseMissing('health_sync_samples', ['user_id' => $user->id]);
});

it('explains when a saved meal photo is unavailable', function (): void {
    Storage::fake(MealPhoto::DISK);
    $user = User::factory()->create();
    $sample = createLogbookMeal($user);
    MealPhoto::factory()->create(['user_id' => $user->id, 'health_group_id' => $sample->group_id]);
    $this->actingAs($user);

    visit(route('health-entries.index', absolute: false))
        ->assertSee('Photo unavailable')
        ->click('button[aria-label="View meal photo"]')
        ->assertSeeIn('[role="dialog"]', 'Photo unavailable')
        ->assertSeeIn('[role="dialog"]', '490')
        ->assertNoJavaScriptErrors();
});

it('keeps photos and all readings visible for meals with other grouped samples', function (bool $withGlucose): void {
    Storage::fake(MealPhoto::DISK);
    $user = User::factory()->create();
    $sample = createLogbookMeal($user);
    $photo = MealPhoto::factory()->create(['user_id' => $user->id, 'health_group_id' => $sample->group_id]);
    Storage::disk(MealPhoto::DISK)->put($photo->path, (string) UploadedFile::fake()->image('meal.jpg')->get());
    HealthSyncSample::factory()->create([
        'user_id' => $user->id,
        'group_id' => $sample->group_id,
        'type_identifier' => $withGlucose ? 'bloodGlucose' : 'weight',
        'value' => $withGlucose ? 120 : 75,
        'measured_at' => $sample->measured_at,
    ]);
    $this->actingAs($user);

    visit(route('health-entries.index', ['type' => 'food'], absolute: false))
        ->assertSee($withGlucose ? '6.7 mmol/L' : '75 kg')
        ->assertSee('490')
        ->assertVisible('button[aria-label="View meal photo"]')
        ->click('button[aria-label="View meal photo"]')
        ->assertVisible('[role="dialog"] img')
        ->assertSeeIn('[role="dialog"]', '490')
        ->assertNoJavaScriptErrors();
})->with(['food and glucose' => [true], 'food and vitals' => [false]]);
