<?php

declare(strict_types=1);

use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;

it('requires authentication', function (): void {
    $response = $this->get(route('meal-plans.weekly'));

    $response->assertRedirectToRoute('login');
});

it('renders weekly meal plans page for authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.weekly'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('meal-plans/weekly/show-weekly-plan')
            ->has('mealPlan')
            ->has('currentDay')
            ->has('navigation'));
});

it('shows empty state when user has no meal plans', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.weekly'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('meal-plans/weekly/show-weekly-plan')
            ->where('mealPlan', null)
            ->where('currentDay', null)
            ->where('navigation', null));
});

it('displays the latest weekly meal plan by default', function (): void {
    $user = User::factory()->create();

    // Create older meal plan
    MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create(['created_at' => now()->subDays(7)]);

    // Create newer meal plan
    $latestPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create(['created_at' => now()]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.weekly'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('mealPlan.id', $latestPlan->id));
});

it('defaults to current day of week', function (): void {
    $user = User::factory()->create();
    $currentDayOfWeek = now()->dayOfWeekIso; // 1 = Monday, 7 = Sunday

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
        ->get(route('meal-plans.weekly'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', $currentDayOfWeek));
});

it('displays meals for a specific day', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create();

    // Create meals for different days
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
        ->get(route('meal-plans.weekly', ['day' => 3]));

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

    // Test day < 1
    $response = $this->actingAs($user)
        ->get(route('meal-plans.weekly', ['day' => -5]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', 1));

    // Test day > 7
    $response = $this->actingAs($user)
        ->get(route('meal-plans.weekly', ['day' => 100]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', 7));
});

it('calculates daily stats correctly', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->create();

    // Create meals with known values
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
        ->get(route('meal-plans.weekly', ['day' => 1]));

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
        ->get(route('meal-plans.weekly', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('navigation.has_previous', true)
            ->where('navigation.previous_day', 7) // Loops to Sunday
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
        ->get(route('meal-plans.weekly', ['day' => 7]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('navigation.has_next', true)
            ->where('navigation.next_day', 1) // Loops to Monday
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
        ->get(route('meal-plans.weekly', ['day' => 3]));

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

    // Create meals in reverse order
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
        ->get(route('meal-plans.weekly', ['day' => 1]));

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
            'protein_grams' => 40, // 40*4 = 160 cal (40%)
            'carbs_grams' => 40,   // 40*4 = 160 cal (40%)
            'fat_grams' => 9,      // 9*9 = 81 cal (20%)
        ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.weekly', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('currentDay.meals.0.macro_percentages')
            ->where('currentDay.meals.0.macro_percentages.protein', 39.9) // Rounding
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
        ->get(route('meal-plans.weekly', ['day' => 1]));

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

it('only shows weekly meal plans not monthly or custom', function (): void {
    $user = User::factory()->create();

    // Create non-weekly meal plans
    MealPlan::factory()
        ->monthly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create();

    MealPlan::factory()
        ->custom(14)
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create();

    // Create weekly meal plan
    $weeklyPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.weekly', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('mealPlan.id', $weeklyPlan->id)
            ->where('mealPlan.type', 'weekly'));
});

it('does not show other users meal plans', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create meal plan for other user
    MealPlan::factory()
        ->weekly()
        ->for($otherUser)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.weekly', ['day' => 1]));

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

    // Create meal with known macros
    Meal::factory()
        ->for($mealPlan)
        ->forDay(1)
        ->create([
            'protein_grams' => 50,  // 50*4 = 200 cal
            'carbs_grams' => 50,    // 50*4 = 200 cal
            'fat_grams' => 22,      // 22*9 = 198 cal (~200)
        ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.weekly', ['day' => 1]));

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

    // Navigate to day 2
    $response = $this->actingAs($user)
        ->get(route('meal-plans.weekly', ['day' => 2]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', 2));
});

it('includes job tracking data when available', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create();

    $jobTracking = App\Models\JobTracking::factory()->create([
        'user_id' => $user->id,
        'job_type' => App\Jobs\ProcessMealPlanJob::JOB_TYPE,
        'status' => App\Enums\JobStatus::Processing,
        'progress' => 50,
        'message' => 'Generating your meal plan...',
    ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.weekly', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('jobTracking.status', 'processing')
            ->where('jobTracking.progress', 50)
            ->where('jobTracking.message', 'Generating your meal plan...'));
});

it('handles null job tracking gracefully', function (): void {
    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()
        ->weekly()
        ->for($user)
        ->has(Meal::factory()->breakfast()->forDay(1), 'meals')
        ->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.weekly', ['day' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('jobTracking', null));
});
