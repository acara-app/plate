<?php

declare(strict_types=1);

use App\Ai\Agents\FoodItemEstimatorAgent;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\TierEntitlement;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Enums\SubscriptionTier;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\MealPhoto;
use App\Models\User;
use App\Models\UserProfile;
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

function storeLogbookPhoto(User $user, HealthSyncSample $sample): void
{
    $photo = MealPhoto::factory()->create(['user_id' => $user->id, 'health_group_id' => $sample->group_id]);
    Storage::disk(MealPhoto::DISK)->put($photo->path, (string) UploadedFile::fake()->image('meal.jpg', 960, 640)->get());
}

it('shows a day’s meal as a card and opens its nutrition page with the photo on mobile', function (): void {
    Storage::fake(MealPhoto::DISK);
    $user = User::factory()->create();
    $sample = createLogbookMeal($user);
    storeLogbookPhoto($user, $sample);
    $this->actingAs($user);

    visit(route('health-entries.index', ['date' => '2026-10-16'], absolute: false))->on()->mobile()
        ->assertSee('Grilled chicken breast, Steamed rice')
        ->assertSee('490 kcal')
        ->click('a[href="/health-entries/'.$sample->id.'"]')
        ->assertSee('Nutrition')
        ->assertSee('Ingredients')
        ->assertSee('1 breast (170g)')
        ->click('button[aria-label="View meal photo"]')
        ->assertVisible('[role="dialog"] img')
        ->keys('[role="dialog"]', 'Escape')
        ->assertMissing('[role="dialog"]')
        ->click('Done')
        ->assertSee('Today')
        ->assertQueryStringHas('date', '2026-10-16')
        ->assertSee('Grilled chicken breast, Steamed rice')
        ->assertNoJavaScriptErrors();
});

it('moves between days and weeks with the week strip', function (): void {
    $user = User::factory()->create();
    createLogbookMeal($user);
    HealthSyncSample::factory()->create([
        'user_id' => $user->id,
        'type_identifier' => 'exerciseMinutes',
        'value' => 30,
        'group_id' => null,
        'measured_at' => '2026-10-17T03:57:00Z',
    ]);
    $this->actingAs($user);

    visit(route('health-entries.index', ['date' => '2026-10-17'], absolute: false))->withLocale('en-US')
        ->assertSee('30 min')
        ->assertDontSee('Grilled chicken breast')
        ->click('a[aria-label^="Friday, October 16"]')
        ->assertSee('Grilled chicken breast, Steamed rice')
        ->assertQueryStringHas('date', '2026-10-16')
        ->assertDontSee('30 min')
        ->assertAttribute('a[aria-label^="Friday, October 16"]', 'aria-current', 'date')
        ->click('a[aria-label="Previous week"]')
        ->assertSee('Nothing logged on this day')
        ->assertQueryStringHas('date', '2026-10-09')
        ->assertNoJavaScriptErrors();
});

it('renames an ingredient, updates its nutrition and saves the meal with Done', function (): void {
    $user = User::factory()->create();
    $sample = createLogbookMeal($user);
    FoodItemEstimatorAgent::fake([[
        'calories' => 240, 'protein' => 26, 'carbs' => 8, 'fat' => 13,
        'grams' => 170, 'portion' => '170 g (1 block)',
    ]]);
    $this->actingAs($user);

    visit(route('health-entries.show', $sample, absolute: false))->on()->mobile()
        ->click('button:has-text("Grilled chicken breast")')
        ->fill('#ingredient-name', 'Baked tofu')
        ->click('Update nutrition for “Baked tofu”')
        ->assertValue('#ingredient-portion', '170 g (1 block)')
        ->assertValue('#ingredient-calories', '240')
        ->press('Apply')
        ->assertSee('Baked tofu, Steamed rice')
        ->assertSee('450')
        ->press('Done')
        ->assertSee('450 kcal')
        ->assertQueryStringHas('date', '2026-10-16')
        ->assertSee('Baked tofu, Steamed rice')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('health_sync_samples', [
        'user_id' => $user->id,
        'type_identifier' => 'dietaryEnergy',
        'value' => 450,
        'notes' => 'Baked tofu, Steamed rice',
    ]);
    FoodItemEstimatorAgent::assertPromptedTimes(1);
});

it('keeps ingredient edits when nutrition estimation fails', function (bool $creditLimit): void {
    $user = User::factory()->create();
    $sample = createLogbookMeal($user);
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
    visit(route('health-entries.show', $sample, absolute: false))
        ->click('button:has-text("Grilled chicken breast")')
        ->fill('#ingredient-name', 'Baked tofu')
        ->click('Update nutrition for “Baked tofu”')
        ->assertSee($creditLimit
            ? "You've reached your AI credit limit. Please adjust the numbers by hand for now."
            : "Couldn't update the nutrition. Please adjust the numbers.")
        ->assertValue('#ingredient-name', 'Baked tofu')
        ->assertValue('#ingredient-calories', '280')
        ->assertVisible('[role="dialog"]')
        ->assertNoJavaScriptErrors();

    if ($creditLimit) {
        FoodItemEstimatorAgent::assertNeverPrompted();
        Exceptions::assertReported(UsageLimitExceededException::class);
    } else {
        Exceptions::assertReported(RuntimeException::class);
    }
})->with(['provider failure' => [false], 'credit limit' => [true]]);

it('adds and removes ingredients and changes the meal type before saving', function (): void {
    $user = User::factory()->create();
    $sample = createLogbookMeal($user);
    $this->actingAs($user);

    visit(route('health-entries.show', $sample, absolute: false))
        ->click('section[aria-labelledby="ingredients-heading"] button:has-text("Add")')
        ->fill('#ingredient-name', 'Green salad')
        ->fill('#ingredient-calories', '50')
        ->fill('#ingredient-carbs', '6')
        ->press('Apply')
        ->assertSee('3 items')
        ->assertMissing('[role="dialog"]')
        ->click('button:has-text("Steamed rice")')
        ->assertSee('Edit ingredient')
        ->press('Remove ingredient')
        ->assertDontSeeIn('section[aria-labelledby="ingredients-heading"]', 'Steamed rice')
        ->assertSee('330')
        ->click('[role="radio"]:has-text("Dinner")')
        ->click('button[aria-label="Back to Logbook"]')
        ->assertSee('Discard your changes?')
        ->press('Keep editing')
        ->press('Done')
        ->assertSee('330 kcal')
        ->assertQueryStringHas('date', '2026-10-16')
        ->assertNoJavaScriptErrors();

    $calories = HealthSyncSample::query()->whereBelongsTo($user)->where('type_identifier', 'dietaryEnergy')->sole();

    expect($calories->value)->toBe(330.0)
        ->and($calories->metadata['meal_type'])->toBe('dinner')
        ->and(array_column($calories->metadata['snap_to_track']['items'], 'name'))->toBe(['Grilled chicken breast', 'Green salad']);
});

it('keeps Done within reach and shows why a meal could not be saved', function (): void {
    $user = User::factory()->create();
    $sample = createLogbookMeal($user, large: true);
    $this->actingAs($user);

    $page = visit(route('health-entries.show', $sample, absolute: false))->on()->mobile()
        ->assertSee('7 items');
    $page->script('document.querySelector("section[aria-labelledby=ingredients-heading] li:nth-child(7) button").scrollIntoView({ block: "center" })');
    $page->click('button:has-text("Olive oil")')
        ->fill('#ingredient-calories', '5000')
        ->press('Apply');

    expect($page->script('(() => { const r = [...document.querySelectorAll("button")].find((b) => b.textContent.trim() === "Done").getBoundingClientRect(); return r.top >= 0 && r.bottom <= innerHeight; })()'))->toBeTrue();

    $page->press('Done')
        ->assertSee('We couldn’t save your changes. Check the highlighted items.')
        ->assertSee('The combined calories of all items may not exceed 5000.')
        ->assertSee('Olive oil')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('health_sync_samples', ['user_id' => $user->id, 'type_identifier' => 'dietaryEnergy', 'value' => 140]);
});

it('adds a manual vital sign and confirms before deleting it from its page', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    visit(route('health-entries.index', absolute: false))
        ->assertSee('Nothing logged yet today')
        ->press('Add entry')
        ->click('[role="dialog"] button:has-text("Vitals")')
        ->fill('blood_pressure_diastolic', '80')
        ->press('Save')
        ->assertMissing('[role="dialog"]')
        ->click('a:has-text("Vitals")')
        ->assertSee('–/80 BP')
        ->click('button[aria-label="More entry actions"]')
        ->click('Delete entry')
        ->assertVisible('[role="alertdialog"]')
        ->press('Cancel')
        ->assertSee('–/80 BP')
        ->click('button[aria-label="More entry actions"]')
        ->click('Delete entry')
        ->press('Delete entry')
        ->assertSee('Nothing logged yet today')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseMissing('health_sync_samples', ['user_id' => $user->id]);
});

it('explains when a saved meal photo is unavailable', function (): void {
    Storage::fake(MealPhoto::DISK);
    $user = User::factory()->create();
    $sample = createLogbookMeal($user);
    MealPhoto::factory()->create(['user_id' => $user->id, 'health_group_id' => $sample->group_id]);
    $this->actingAs($user);

    visit(route('health-entries.index', ['date' => '2026-10-16'], absolute: false))
        ->assertSee('Photo unavailable')
        ->click('a[href="/health-entries/'.$sample->id.'"]')
        ->assertSee('Photo unavailable')
        ->assertSee('490')
        ->assertNoJavaScriptErrors();
});

it('edits a reading logged with a meal without losing the meal or its photo', function (bool $withGlucose): void {
    Storage::fake(MealPhoto::DISK);
    $user = User::factory()->create();
    $sample = createLogbookMeal($user);
    storeLogbookPhoto($user, $sample);
    HealthSyncSample::factory()->create([
        'user_id' => $user->id,
        'group_id' => $sample->group_id,
        'type_identifier' => $withGlucose ? 'bloodGlucose' : 'weight',
        'value' => $withGlucose ? 120 : 75,
        'measured_at' => $sample->measured_at,
        'metadata' => $withGlucose ? ['glucose_reading_type' => 'post-meal'] : null,
    ]);
    $this->actingAs($user);

    visit(route('health-entries.index', ['date' => '2026-10-16'], absolute: false))
        ->assertSee($withGlucose ? '6.7 mmol/L' : '75 kg')
        ->assertSee('490 kcal')
        ->click('a[href="/health-entries/'.$sample->id.'"]')
        ->assertSee('Also logged')
        ->click($withGlucose ? 'button[aria-label="Edit Glucose"]' : 'button[aria-label="Edit Vitals"]')
        ->fill($withGlucose ? 'glucose_value' : 'weight', $withGlucose ? '7.5' : '76')
        ->press('Save changes')
        ->assertMissing('[role="dialog"]')
        ->assertSee($withGlucose ? '7.5 mmol/L' : '76 kg')
        ->assertSee('490')
        ->assertVisible('button[aria-label="View meal photo"]')
        ->assertNoJavaScriptErrors();

    expect(HealthSyncSample::query()->where('group_id', $sample->group_id)->count())->toBe(5)
        ->and(MealPhoto::query()->where('health_group_id', $sample->group_id)->exists())->toBeTrue();
})->with(['food and glucose' => [true], 'food and vitals' => [false]]);

it('shows the daily calorie goal and macro progress for a completed profile', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['age' => 30, 'date_of_birth' => null, 'height' => 180, 'weight' => 80, 'sex' => Sex::Male, 'goal_choice' => GoalChoice::HealthyEating, 'derived_activity_multiplier' => 1.5]);
    createLogbookMeal($user);
    $this->actingAs($user);

    visit(route('health-entries.index', ['date' => '2026-10-16'], absolute: false))->on()->mobile()
        ->assertSee('Daily calorie goal')
        ->assertSeeIn('#calorie-goal-heading + p', '490')
        ->assertSeeIn('#calorie-goal-heading + p', '/2370 kcal')
        ->assertSee('Macronutrients')
        ->assertSee('/119 g')
        ->assertSee('33%')
        ->assertSee('/33 g')
        ->assertDontSee('Complete your profile to see your daily goals')
        ->assertNoJavaScriptErrors();
});

it('invites people who skipped onboarding to set up their daily goals', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    visit(route('health-entries.index', absolute: false))->on()->mobile()
        ->assertSee('Daily calorie goal')
        ->click('Complete your profile to see your daily goals')
        ->assertPathIs('/onboarding/biometrics')
        ->assertNoJavaScriptErrors();
});

it('adds fiber to an ingredient and saves it with the meal', function (): void {
    $user = User::factory()->create();
    $sample = createLogbookMeal($user);
    $this->actingAs($user);

    visit(route('health-entries.show', $sample, absolute: false))
        ->click('button:has-text("Steamed rice")')
        ->assertSee('Edit ingredient')
        ->fill('#ingredient-fiber', '1.8')
        ->press('Apply')
        ->assertMissing('[role="dialog"]')
        ->assertSee('1.8g')
        ->press('Done')
        ->assertSee('490 kcal')
        ->assertNoJavaScriptErrors();

    expect(HealthSyncSample::query()->whereBelongsTo($user)->where('type_identifier', 'fiber')->value('value'))->toBe(1.8);
});
