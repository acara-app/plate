<?php

declare(strict_types=1);

use App\Actions\GetUserHealthEntriesAction;
use App\Enums\HealthEntryType;
use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
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

it('counts a multi-sample meal as one entry, not one row per sample', function (): void {
    $user = User::factory()->create();
    seedMeal($user, 'meal-1', now()->subHour());
    HealthSyncSample::factory()->weight()->create([
        'user_id' => $user->id,
        'group_id' => null,
        'measured_at' => now()->subHours(2),
    ]);

    $paginator = resolve(GetUserHealthEntriesAction::class)->handle($user);

    expect($paginator->total())->toBe(2)
        ->and($paginator->items())->toHaveCount(2);
});

it('keeps every sample of a meal on the same page', function (): void {
    $user = User::factory()->create();
    seedMeal($user, 'meal-a', now()->subHour());
    seedMeal($user, 'meal-b', now()->subHours(3));

    $page = resolve(GetUserHealthEntriesAction::class)->handle($user, perPage: 1);

    expect($page->total())->toBe(2)
        ->and($page->lastPage())->toBe(2)
        ->and($page->items())->toHaveCount(1);

    $entry = $page->items()[0];

    expect($entry['group_id'])->toBe('meal-a')
        ->and($entry['carbs_grams'])->not->toBeNull()
        ->and($entry['protein_grams'])->not->toBeNull()
        ->and($entry['fat_grams'])->not->toBeNull();
});

it('orders entries by most recent first and scopes to the requesting user', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    seedMeal($user, 'older', now()->subHours(5));
    seedMeal($user, 'newer', now()->subHour());
    seedMeal($other, 'theirs', now());

    $paginator = resolve(GetUserHealthEntriesAction::class)->handle($user);

    expect($paginator->total())->toBe(2)
        ->and($paginator->items()[0]['group_id'])->toBe('newer')
        ->and($paginator->items()[1]['group_id'])->toBe('older');
});

it('paginates matching groups and keeps their other readings intact', function (): void {
    $user = User::factory()->create();
    seedMeal($user, 'meal-newer', now()->subHour());
    seedMeal($user, 'meal-older', now()->subHours(3));
    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'group_id' => 'meal-newer',
        'value' => 120,
        'measured_at' => now()->subHour(),
    ]);
    HealthSyncSample::factory()->weight()->count(20)->create(['user_id' => $user->id]);

    $paginator = resolve(GetUserHealthEntriesAction::class)->handle($user, perPage: 1, type: HealthEntryType::Food);

    expect($paginator->total())->toBe(2)
        ->and($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]['group_id'])->toBe('meal-newer')
        ->and($paginator->items()[0]['glucose_value'])->toBe(120.0)
        ->and($paginator->items()[0]['protein_grams'])->not->toBeNull()
        ->and($paginator->nextPageUrl())->toContain('type=food', 'page=2');

    $this->app['request']->query->set('page', 2);
    $nextPage = resolve(GetUserHealthEntriesAction::class)->handle($user, perPage: 1, type: HealthEntryType::Food);

    expect($nextPage->items()[0]['group_id'])->toBe('meal-older');
});
