<?php

declare(strict_types=1);

use App\Ai\Tools\GetMealGlucoseResponse;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Services\MealGlucoseResponseService;
use Laravel\Ai\Tools\Request;

use function Pest\Laravel\actingAs;

function glucoseInsightTool(): GetMealGlucoseResponse
{
    return new GetMealGlucoseResponse(new MealGlucoseResponseService);
}

it('requires opt-in before returning meal glucose insights', function (): void {
    actingAs(User::factory()->create());

    $result = json_decode(glucoseInsightTool()->handle(new Request(['days' => 7])), true);

    expect($result)->toHaveKey('opt_in_required')
        ->and($result['opt_in_required'])->toBeTrue();
});

it('returns observational insights for an opted-in user with data', function (): void {
    $user = User::factory()->create(['settings' => ['glucose_meal_insights_enabled' => true]]);
    actingAs($user);

    $mealAt = now()->subHours(5);
    HealthSyncSample::factory()->carbohydrates()->create(['user_id' => $user->id, 'group_id' => 'm1', 'measured_at' => $mealAt]);
    HealthSyncSample::factory()->bloodGlucose()->create(['user_id' => $user->id, 'value' => 100, 'measured_at' => $mealAt->copy()->subMinutes(20)]);
    HealthSyncSample::factory()->bloodGlucose()->create(['user_id' => $user->id, 'value' => 150, 'measured_at' => $mealAt->copy()->addMinutes(60)]);

    $result = json_decode(glucoseInsightTool()->handle(new Request(['days' => 7])), true);

    expect($result['success'])->toBeTrue()
        ->and($result['insights'])->toHaveCount(1)
        ->and($result['insights'][0]['summary'])->toContain('After this meal')
        ->and($result['notice'])->not->toBeEmpty();
});

it('includes comparable-meal context when the user has enough similar meals', function (): void {
    $user = User::factory()->create(['settings' => ['glucose_meal_insights_enabled' => true]]);
    actingAs($user);

    foreach ([1, 2, 3, 4] as $daysAgo) {
        $at = now()->subDays($daysAgo);
        HealthSyncSample::factory()->carbohydrates()->create(['user_id' => $user->id, 'group_id' => 'p'.$daysAgo, 'value' => 40, 'measured_at' => $at]);
        HealthSyncSample::factory()->bloodGlucose()->create(['user_id' => $user->id, 'value' => 100, 'measured_at' => $at->copy()->subMinutes(20)]);
        HealthSyncSample::factory()->bloodGlucose()->create(['user_id' => $user->id, 'value' => 140, 'measured_at' => $at->copy()->addMinutes(60)]);
    }

    $result = json_decode(glucoseInsightTool()->handle(new Request(['days' => 7])), true);

    expect($result['insights'])->not->toBeEmpty()
        ->and($result['insights'][0]['comparable'])->toContain('similar meals');
});

it('reports when an opted-in user has no comparable glucose data', function (): void {
    $user = User::factory()->create(['settings' => ['glucose_meal_insights_enabled' => true]]);
    actingAs($user);

    $result = json_decode(glucoseInsightTool()->handle(new Request(['days' => 7])), true);

    expect($result['success'])->toBeTrue()
        ->and($result['insights'])->toBe([])
        ->and($result['message'])->toContain('No comparable glucose data');
});
