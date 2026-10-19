<?php

declare(strict_types=1);

use App\Actions\GetUserHealthEntriesAction;
use App\Actions\GetUserLoggedDatesAction;
use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

function seedMeal(User $user, string $groupId, CarbonInterface $at): void
{
    foreach ([HealthSyncType::Carbohydrates, HealthSyncType::Protein, HealthSyncType::TotalFat] as $type) {
        HealthSyncSample::factory()->create([
            'user_id' => $user->id,
            'type_identifier' => $type->value,
            'group_id' => $groupId,
            'measured_at' => $at,
        ]);
    }
}

it('returns each meal once with its other grouped readings', function (): void {
    $user = User::factory()->create();
    $day = CarbonImmutable::parse('2026-10-18', 'UTC');
    seedMeal($user, 'meal-1', $day->setTime(12, 30));
    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'group_id' => 'meal-1',
        'value' => 120,
        'measured_at' => $day->setTime(12, 30),
    ]);
    HealthSyncSample::factory()->weight()->create([
        'user_id' => $user->id,
        'group_id' => null,
        'measured_at' => $day->setTime(8, 0),
    ]);

    $entries = resolve(GetUserHealthEntriesAction::class)->handle($user, $day);

    expect($entries)->toHaveCount(2)
        ->and($entries[0]['group_id'])->toBe('meal-1')
        ->and($entries[0]['glucose_value'])->toBe(120.0)
        ->and($entries[0]['protein_grams'])->not->toBeNull()
        ->and($entries[0]['meal_type'])->toBe('lunch')
        ->and($entries[1]['weight'])->not->toBeNull();
});

it('orders the day newest first and scopes it to the requesting user and local day', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $day = CarbonImmutable::parse('2026-10-18', 'America/Los_Angeles');

    seedMeal($user, 'breakfast', $day->setTime(7, 0)->utc());
    seedMeal($user, 'late-snack', $day->setTime(23, 45)->utc());
    seedMeal($user, 'next-day', $day->addDay()->setTime(0, 15)->utc());
    seedMeal($other, 'theirs', $day->setTime(9, 0)->utc());

    $entries = resolve(GetUserHealthEntriesAction::class)->handle($user, $day);

    expect($entries->pluck('group_id')->all())->toBe(['late-snack', 'breakfast'])
        ->and($entries->pluck('meal_type')->all())->toBe(['snack', 'breakfast']);
});

it('shows fiber logged with a meal but keeps standalone synced fiber off the logbook', function (): void {
    $user = User::factory()->create();
    $day = CarbonImmutable::parse('2026-10-18', 'UTC');
    seedMeal($user, 'lunch', $day->setTime(12, 0));
    HealthSyncSample::factory()->create(['user_id' => $user->id, 'type_identifier' => 'fiber', 'value' => 9.5, 'group_id' => 'lunch', 'measured_at' => $day->setTime(12, 0)]);
    HealthSyncSample::factory()->create(['user_id' => $user->id, 'type_identifier' => 'fiber', 'value' => 4, 'group_id' => null, 'measured_at' => $day->setTime(18, 0)]);
    HealthSyncSample::factory()->create(['user_id' => $user->id, 'type_identifier' => 'fiber', 'value' => 3, 'group_id' => null, 'measured_at' => $day->addDay()->setTime(9, 0)]);

    $entries = resolve(GetUserHealthEntriesAction::class)->handle($user, $day);
    $loggedDates = resolve(GetUserLoggedDatesAction::class)->handle($user, $day, $day->addDay());

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['fiber_grams'])->toBe(9.5)
        ->and($loggedDates)->toBe(['2026-10-18']);
});
