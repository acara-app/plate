<?php

declare(strict_types=1);

use App\Models\HealthSyncSample;
use App\Models\User;
use App\Services\MealGlucoseResponseService;
use Carbon\CarbonInterface;

function seedGlucoseReading(User $user, CarbonInterface $at, float $value): void
{
    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'value' => $value,
        'measured_at' => $at,
    ]);
}

function seedMealMacroSample(User $user, CarbonInterface $at, string $groupId, ?float $carbs = null): void
{
    HealthSyncSample::factory()->carbohydrates()->create([
        'user_id' => $user->id,
        'measured_at' => $at,
        'group_id' => $groupId,
        ...($carbs !== null ? ['value' => $carbs] : []),
    ]);
}

function seedComparableMeal(User $user, string $groupId, float $carbs, float $rise, int $daysAgo): void
{
    $at = now()->subDays($daysAgo);
    seedMealMacroSample($user, $at, $groupId, $carbs);
    seedGlucoseReading($user, $at->copy()->subMinutes(20), 100);
    seedGlucoseReading($user, $at->copy()->addMinutes(60), 100 + $rise);
}

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->service = new MealGlucoseResponseService;
    $this->mealAt = now()->subHours(6);
});

it('reports the nearest baseline and the peak rise around a meal', function (): void {
    seedGlucoseReading($this->user, $this->mealAt->copy()->subMinutes(60), 100);
    seedGlucoseReading($this->user, $this->mealAt->copy()->subMinutes(20), 110);
    seedGlucoseReading($this->user, $this->mealAt->copy()->addMinutes(60), 140);
    seedGlucoseReading($this->user, $this->mealAt->copy()->addMinutes(120), 155);

    $response = $this->service->forMeal($this->user, $this->mealAt);

    expect($response)->not->toBeNull()
        ->and($response->baseline)->toBe(110.0)
        ->and($response->peak)->toBe(155.0)
        ->and($response->delta)->toBe(45.0)
        ->and($response->readingsInWindow)->toBe(2)
        ->and($response->overlapping)->toBeFalse();
});

it('returns null when there is no pre-meal baseline reading', function (): void {
    seedGlucoseReading($this->user, $this->mealAt->copy()->addMinutes(60), 140);

    expect($this->service->forMeal($this->user, $this->mealAt))->toBeNull();
});

it('returns null when there is no in-window reading', function (): void {
    seedGlucoseReading($this->user, $this->mealAt->copy()->subMinutes(30), 100);

    expect($this->service->forMeal($this->user, $this->mealAt))->toBeNull();
});

it('counts only readings inside the response window', function (): void {
    seedGlucoseReading($this->user, $this->mealAt->copy()->subMinutes(45), 100);
    seedGlucoseReading($this->user, $this->mealAt->copy()->addMinutes(29), 200);
    seedGlucoseReading($this->user, $this->mealAt->copy()->addMinutes(30), 130);
    seedGlucoseReading($this->user, $this->mealAt->copy()->addMinutes(180), 150);
    seedGlucoseReading($this->user, $this->mealAt->copy()->addMinutes(181), 999);

    $response = $this->service->forMeal($this->user, $this->mealAt);

    expect($response->readingsInWindow)->toBe(2)
        ->and($response->peak)->toBe(150.0);
});

it('ignores a baseline reading older than the baseline window', function (): void {
    seedGlucoseReading($this->user, $this->mealAt->copy()->subMinutes(120), 100);
    seedGlucoseReading($this->user, $this->mealAt->copy()->addMinutes(60), 140);

    expect($this->service->forMeal($this->user, $this->mealAt))->toBeNull();
});

it('flags an overlapping meal inside the response window', function (): void {
    seedGlucoseReading($this->user, $this->mealAt->copy()->subMinutes(30), 100);
    seedGlucoseReading($this->user, $this->mealAt->copy()->addMinutes(60), 140);
    seedMealMacroSample($this->user, $this->mealAt->copy()->addMinutes(90), 'g2');

    $response = $this->service->forMeal($this->user, $this->mealAt, excludeGroupId: 'g1');

    expect($response->overlapping)->toBeTrue();
});

it('flags a standalone meal entry without a group id as overlapping', function (): void {
    seedGlucoseReading($this->user, $this->mealAt->copy()->subMinutes(30), 100);
    seedGlucoseReading($this->user, $this->mealAt->copy()->addMinutes(60), 140);
    HealthSyncSample::factory()->carbohydrates()->create([
        'user_id' => $this->user->id,
        'measured_at' => $this->mealAt->copy()->addMinutes(90),
        'group_id' => null,
    ]);

    $response = $this->service->forMeal($this->user, $this->mealAt, excludeGroupId: 'g1');

    expect($response->overlapping)->toBeTrue();
});

it("does not flag overlap for the meal's own group or meals outside the window", function (): void {
    seedGlucoseReading($this->user, $this->mealAt->copy()->subMinutes(30), 100);
    seedGlucoseReading($this->user, $this->mealAt->copy()->addMinutes(60), 140);
    seedMealMacroSample($this->user, $this->mealAt->copy()->addMinutes(60), 'g1');
    seedMealMacroSample($this->user, $this->mealAt->copy()->addMinutes(200), 'g3');

    $response = $this->service->forMeal($this->user, $this->mealAt, excludeGroupId: 'g1');

    expect($response->overlapping)->toBeFalse();
});

it("only considers the requesting user's own readings", function (): void {
    $other = User::factory()->create();
    seedGlucoseReading($other, $this->mealAt->copy()->subMinutes(30), 100);
    seedGlucoseReading($other, $this->mealAt->copy()->addMinutes(60), 140);

    expect($this->service->forMeal($this->user, $this->mealAt))->toBeNull();
});

it('returns recent meals with a computable response, most recent first', function (): void {
    $mealA = now()->subHours(8);
    $mealB = now()->subHours(4);
    $mealC = now()->subHours(2);

    seedMealMacroSample($this->user, $mealA, 'a1');
    seedGlucoseReading($this->user, $mealA->copy()->subMinutes(20), 100);
    seedGlucoseReading($this->user, $mealA->copy()->addMinutes(60), 150);

    seedMealMacroSample($this->user, $mealB, 'b1');
    seedGlucoseReading($this->user, $mealB->copy()->subMinutes(20), 110);
    seedGlucoseReading($this->user, $mealB->copy()->addMinutes(60), 130);

    seedMealMacroSample($this->user, $mealC, 'c1');

    $responses = $this->service->recentResponses($this->user);

    expect($responses)->toHaveCount(2)
        ->and($responses[0]['mealAt']->toIso8601String())->toBe($mealB->toIso8601String())
        ->and($responses[1]['mealAt']->toIso8601String())->toBe($mealA->toIso8601String());
});

it('respects the recent-responses limit', function (): void {
    foreach ([6, 4, 2] as $index => $hoursAgo) {
        $at = now()->subHours($hoursAgo);
        seedMealMacroSample($this->user, $at, 'g'.$index);
        seedGlucoseReading($this->user, $at->copy()->subMinutes(20), 100);
        seedGlucoseReading($this->user, $at->copy()->addMinutes(60), 140);
    }

    expect($this->service->recentResponses($this->user, days: 7, limit: 2))->toHaveCount(2);
});

it('aggregates comparable meals into a median and range', function (): void {
    seedComparableMeal($this->user, 'p1', 40.0, 30, 1);
    seedComparableMeal($this->user, 'p2', 45.0, 40, 2);
    seedComparableMeal($this->user, 'p3', 35.0, 50, 3);

    $pattern = $this->service->carbBandPattern($this->user, 40.0);

    expect($pattern)->not->toBeNull()
        ->and($pattern->count)->toBe(3)
        ->and($pattern->median)->toBe(40.0)
        ->and($pattern->min)->toBe(30.0)
        ->and($pattern->max)->toBe(50.0);
});

it('withholds the pattern below the minimum number of comparable meals', function (): void {
    seedComparableMeal($this->user, 'p1', 40.0, 30, 1);
    seedComparableMeal($this->user, 'p2', 45.0, 40, 2);

    expect($this->service->carbBandPattern($this->user, 40.0))->toBeNull();
});

it('excludes meals outside the carbohydrate band', function (): void {
    seedComparableMeal($this->user, 'p1', 40.0, 30, 1);
    seedComparableMeal($this->user, 'p2', 45.0, 40, 2);
    seedComparableMeal($this->user, 'out', 80.0, 50, 3);

    expect($this->service->carbBandPattern($this->user, 40.0))->toBeNull();
});

it('excludes overlapping comparable meals from the aggregate', function (): void {
    seedComparableMeal($this->user, 'p1', 40.0, 30, 1);
    seedComparableMeal($this->user, 'p2', 45.0, 40, 2);
    seedComparableMeal($this->user, 'p3', 35.0, 50, 3);
    seedMealMacroSample($this->user, now()->subDays(3)->addMinutes(60), 'big', 100.0);

    expect($this->service->carbBandPattern($this->user, 40.0))->toBeNull();
});

it('includes standalone comparable meals without a group id when excluding a group', function (): void {
    seedComparableMeal($this->user, 'target', 40.0, 30, 1);
    seedComparableMeal($this->user, 'p2', 45.0, 40, 2);
    seedComparableMeal($this->user, 'p3', 35.0, 50, 3);

    $at = now()->subDays(4);
    HealthSyncSample::factory()->carbohydrates()->create([
        'user_id' => $this->user->id,
        'measured_at' => $at,
        'group_id' => null,
        'value' => 42.0,
    ]);
    seedGlucoseReading($this->user, $at->copy()->subMinutes(20), 100);
    seedGlucoseReading($this->user, $at->copy()->addMinutes(60), 135);

    $pattern = $this->service->carbBandPattern($this->user, 40.0, excludeGroupId: 'target');

    expect($pattern)->not->toBeNull()
        ->and($pattern->count)->toBe(3);
});

it("excludes the target meal's own group", function (): void {
    seedComparableMeal($this->user, 'target', 40.0, 30, 1);
    seedComparableMeal($this->user, 'p2', 45.0, 40, 2);
    seedComparableMeal($this->user, 'p3', 35.0, 50, 3);

    expect($this->service->carbBandPattern($this->user, 40.0, excludeGroupId: 'target'))->toBeNull();
});
