<?php

declare(strict_types=1);

use App\Enums\GroceryListStatus;
use App\Jobs\GenerateGroceryListJob;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('dispatches generate grocery list job when creating via store endpoint', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $this->actingAs($user)->post(route('meal-plans.grocery-list.store', $mealPlan));

    Queue::assertPushed(GenerateGroceryListJob::class);
});

it('dispatches generate grocery list job when regenerating', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    GroceryList::factory()->for($mealPlan)->for($user)->create([
        'status' => GroceryListStatus::Active,
    ]);

    $this->actingAs($user)->post(route('meal-plans.grocery-list.store', $mealPlan));

    Queue::assertPushed(GenerateGroceryListJob::class);
});

it('does not dispatch job for existing active grocery list', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()
        ->for($mealPlan)
        ->for($user)
        ->create(['status' => GroceryListStatus::Active]);

    $this->actingAs($user)->get(route('meal-plans.grocery-list.show', $mealPlan));

    Queue::assertNotPushed(GenerateGroceryListJob::class);
});

it('processes job and updates grocery list status', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()
        ->for($mealPlan)
        ->for($user)
        ->create(['status' => GroceryListStatus::Generating]);

    $job = new GenerateGroceryListJob($groceryList);

    expect($job->groceryList->id)->toBe($groceryList->id);
});

it('uses without overlapping middleware', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()
        ->for($mealPlan)
        ->for($user)
        ->create(['status' => GroceryListStatus::Generating]);

    $job = new GenerateGroceryListJob($groceryList);
    $middleware = $job->middleware();

    expect($middleware)->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(Illuminate\Queue\Middleware\WithoutOverlapping::class);
});

it('returns grocery list id as unique id', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()
        ->for($mealPlan)
        ->for($user)
        ->create(['status' => GroceryListStatus::Generating]);

    $job = new GenerateGroceryListJob($groceryList);

    expect($job->uniqueId())->toBe((string) $groceryList->id);
});

it('calls generate items on the action when handled', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()
        ->for($mealPlan)
        ->for($user)
        ->create(['status' => GroceryListStatus::Generating]);

    $job = new GenerateGroceryListJob($groceryList);

    $action = app(App\Actions\GenerateGroceryListAction::class);
    $job->handle($action);

    $groceryList->refresh();
    expect($groceryList->status)->toBe(GroceryListStatus::Active);
});
