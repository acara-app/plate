<?php

declare(strict_types=1);

use App\Enums\DietType;
use App\Http\Controllers\ShowMealPlansController;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;

covers(ShowMealPlansController::class);

it('requires authentication', function (): void {
    $response = $this->get(route('meal-plans.index'));

    $response->assertRedirectToRoute('login');
});

it('renders weekly meal plans page for authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('meal-plans/show')
            ->has('mealPlan')
            ->has('currentDay')
            ->has('navigation'));
});

it('shows empty state when user has no meal plans', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('meal-plans/show')
            ->where('mealPlan', null)
            ->where('currentDay', null)
            ->where('navigation', null));
});

it('displays the latest weekly meal plan by default', function (): void {
    $user = User::factory()->create();

    MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create(['created_at' => now()->subDays(7)]);

    $latestPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create(['created_at' => now()]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('mealPlan.id', $latestPlan->id));
});

it('defaults to current day of week', function (): void {
    $user = User::factory()->create();
    $currentDayOfWeek = now()->dayOfWeekIso;
    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create();

    Meal::factory()
        ->breakfast()
        ->for($mealPlan)
        ->forDay($currentDayOfWeek)
        ->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', $currentDayOfWeek));
});

it('uses session timezone when calculating current day', function (): void {
    $user = User::factory()->create();

    $timezone = 'Asia/Tokyo';
    $currentDayInTokyo = now($timezone)->dayOfWeekIso;

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create();

    Meal::factory()
        ->breakfast()
        ->for($mealPlan)
        ->forDay($currentDayInTokyo)
        ->create();

    $response = $this->actingAs($user)
        ->withSession(['timezone' => $timezone])
        ->get(route('meal-plans.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', $currentDayInTokyo));
});

it('displays meals for a specific day', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create();

    $day3Meal = Meal::factory()
        ->breakfast()
        ->for($mealPlan)
        ->forDay(3)
        ->create(['name' => 'Day 3 Breakfast']);

    Meal::factory()
        ->lunch()
        ->for($mealPlan)
        ->forDay(5)
        ->create(['name' => 'Day 5 Lunch']);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 3]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', 3)
            ->where('currentDay.meals.0.id', $day3Meal->id)
            ->where('currentDay.meals.0.name', 'Day 3 Breakfast')
            ->has('currentDay.meals', 1));
});

it('clamps day parameter to valid range', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => -5]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', 1));

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 100]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', 7));
});

it('handles short duration meal plans correctly', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->custom(3)
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->has(Meal::factory()->breakfast()->forDay(2), 'meals')
        ->has(Meal::factory()->breakfast()->forDay(3), 'meals')
        ->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 5]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', 3)
            ->where('navigation.total_days', 3)
            ->where('navigation.next_day', 1)
            ->where('navigation.previous_day', 2));

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', 1)
            ->where('navigation.previous_day', 3)
            ->where('navigation.next_day', 2));
});

it('defaults to day 1 when current day of week exceeds meal plan duration', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->custom(3)
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->has(Meal::factory()->breakfast()->forDay(2), 'meals')
        ->has(Meal::factory()->breakfast()->forDay(3), 'meals')
        ->create();

    $currentDayOfWeek = now()->dayOfWeekIso;
    $expectedDefaultDay = $currentDayOfWeek <= 3 ? $currentDayOfWeek : 1;

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', $expectedDefaultDay)
            ->where('navigation.total_days', 3));
});

it('calculates daily stats correctly', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create();

    Meal::factory()
        ->for($mealPlan)
        ->forDay(1)
        ->create([
            'calories' => 400,
            'protein_grams' => 30,
            'carbs_grams' => 40,
            'fat_grams' => 15,
        ]);

    Meal::factory()
        ->for($mealPlan)
        ->forDay(1)
        ->create([
            'calories' => 600,
            'protein_grams' => 50,
            'carbs_grams' => 60,
            'fat_grams' => 20,
        ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.daily_stats.total_calories', 1000)
            ->where('currentDay.daily_stats.protein', 80)
            ->where('currentDay.daily_stats.carbs', 100)
            ->where('currentDay.daily_stats.fat', 35));
});

it('provides navigation with looping for previous day', function (): void {
    $user = User::factory()->create();

    MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('navigation.has_previous', true)
            ->where('navigation.previous_day', 7)
            ->where('navigation.total_days', 7));
});

it('provides navigation with looping for next day', function (): void {
    $user = User::factory()->create();

    MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(7), 'meals')
        ->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 7]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('navigation.has_next', true)
            ->where('navigation.next_day', 1)
            ->where('navigation.total_days', 7));
});

it('provides correct navigation for middle days', function (): void {
    $user = User::factory()->create();

    MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(3), 'meals')
        ->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 3]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('navigation.has_previous', true)
            ->where('navigation.has_next', true)
            ->where('navigation.previous_day', 2)
            ->where('navigation.next_day', 4)
            ->where('navigation.total_days', 7));
});

it('returns meals sorted by sort_order', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create();

    $snack = Meal::factory()
        ->for($mealPlan)
        ->forDay(1)
        ->create(['name' => 'Snack', 'sort_order' => 3]);

    $lunch = Meal::factory()
        ->for($mealPlan)
        ->forDay(1)
        ->create(['name' => 'Lunch', 'sort_order' => 1]);

    $breakfast = Meal::factory()
        ->for($mealPlan)
        ->forDay(1)
        ->create(['name' => 'Breakfast', 'sort_order' => 0]);

    $dinner = Meal::factory()
        ->for($mealPlan)
        ->forDay(1)
        ->create(['name' => 'Dinner', 'sort_order' => 2]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.meals.0.name', 'Breakfast')
            ->where('currentDay.meals.1.name', 'Lunch')
            ->where('currentDay.meals.2.name', 'Dinner')
            ->where('currentDay.meals.3.name', 'Snack'));
});

it('includes meal macro percentages', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create();

    Meal::factory()
        ->for($mealPlan)
        ->forDay(1)
        ->create([
            'calories' => 500,
            'protein_grams' => 40,
            'carbs_grams' => 40,
            'fat_grams' => 9,
        ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('currentDay.meals.0.macro_percentages')
            ->where('currentDay.meals.0.macro_percentages.protein', 39.9)
            ->where('currentDay.meals.0.macro_percentages.carbs', 39.9)
            ->where('currentDay.meals.0.macro_percentages.fat', 20.2));
});

it('includes meal plan metadata', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create([
            'name' => 'My Custom Plan',
            'description' => 'A great plan',
            'target_daily_calories' => 2000,
            'metadata' => [
                'preparation_notes' => 'Batch cook proteins on Sunday. Store in airtight containers.',
                'bmi' => 22.5,
            ],
        ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('mealPlan.name', 'My Custom Plan')
            ->where('mealPlan.description', 'A great plan')
            ->where('mealPlan.target_daily_calories', '2000.00')
            ->where('mealPlan.type', 'weekly')
            ->where('mealPlan.duration_days', 7)
            ->where('mealPlan.metadata.preparation_notes', 'Batch cook proteins on Sunday. Store in airtight containers.')
            ->where('mealPlan.metadata.bmi', 22.5)
            ->has('mealPlan.created_at'));
});

it('displays the latest meal plan regardless of type', function (): void {
    $user = User::factory()->create();

    MealPlan::factory()
        ->monthly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create(['created_at' => now()->subDays(5)]);

    MealPlan::factory()
        ->custom(14)
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create(['created_at' => now()->subDays(3)]);

    $latestPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create(['created_at' => now()]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('mealPlan.id', $latestPlan->id)
            ->where('mealPlan.type', 'weekly'));
});

it('does not show other users meal plans', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    MealPlan::factory()
        ->weekly()
        ->for($otherUser)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('mealPlan', null)
            ->where('currentDay', null)
            ->where('navigation', null));
});

it('calculates macronutrient ratios from meals when not set on plan', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create(['macronutrient_ratios' => null]);

    Meal::factory()
        ->for($mealPlan)
        ->forDay(1)
        ->create([
            'protein_grams' => 50,
            'carbs_grams' => 50,
            'fat_grams' => 22,
        ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('mealPlan.macronutrient_ratios')
            ->where('mealPlan.macronutrient_ratios.protein', 33.4)
            ->where('mealPlan.macronutrient_ratios.carbs', 33.4)
            ->where('mealPlan.macronutrient_ratios.fat', 33.1));
});

it('navigates between days with inertia', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->has(Meal::factory()->breakfast()->forDay(2), 'meals')
        ->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 2]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', 2));
});

it('returns day-specific status from metadata when available', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create([
            'metadata' => [
                'day_3_status' => 'generating',
            ],
        ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 3]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', 3)
            ->where('currentDay.status', 'generating'));
});

it('returns generating status when overall plan is generating and day is empty', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create([
            'metadata' => [
                'status' => 'generating',
            ],
        ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 2]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', 2)
            ->where('currentDay.needs_generation', true));
});

it('returns failed status when generating is stale for overall plan', function (): void {
    $user = User::factory()->create();

    MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create([
            'metadata' => [
                'status' => 'generating',
            ],
            'updated_at' => now()->subMinutes(31),
        ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 2]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.status', 'failed')
            ->where('currentDay.needs_generation', true));
});

it('keeps generating status when generation is recent', function (): void {
    $user = User::factory()->create();

    MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create([
            'metadata' => [
                'status' => 'generating',
            ],
            'updated_at' => now()->subMinutes(5),
        ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 2]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.status', 'generating')
            ->where('currentDay.needs_generation', true));
});

it('returns failed status for stale day-specific generating status', function (): void {
    $user = User::factory()->create();

    MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create([
            'metadata' => [
                'status' => 'pending',
                'day_3_status' => 'generating',
            ],
            'updated_at' => now()->subMinutes(31),
        ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 3]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.status', 'failed')
            ->where('currentDay.needs_generation', true));
});

it('shows failed status without auto-retrying when overall status is failed', function (): void {
    $user = User::factory()->create();

    MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create([
            'metadata' => [
                'status' => 'failed',
            ],
        ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.status', 'failed')
            ->where('currentDay.needs_generation', true));
});

it('exposes the diet type catalog on the empty state', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('mealPlan', null)
            ->where('userDietType', DietType::Balanced->value)
            ->where('dietTypes', DietType::toArray()));
});

it('returns the user profile diet type when set', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([
        'calculated_diet_type' => DietType::Mediterranean,
    ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('userDietType', DietType::Mediterranean->value)
            ->where('dietTypes.mediterranean', 'Mediterranean (Gold Standard)'));
});

it('exposes the diet type catalog with an existing meal plan', function (): void {
    $user = User::factory()->create();
    $user->profile()->create([
        'calculated_diet_type' => DietType::Keto,
    ]);

    MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.index', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('userDietType', DietType::Keto->value)
            ->where('dietTypes', DietType::toArray()));
});
