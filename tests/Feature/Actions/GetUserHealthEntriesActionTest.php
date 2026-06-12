<?php

declare(strict_types=1);

use App\Actions\GetUserHealthEntriesAction;
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

    $paginator = app(GetUserHealthEntriesAction::class)->handle($user);

    expect($paginator->total())->toBe(2)
        ->and($paginator->items())->toHaveCount(2);
});

it('keeps every sample of a meal on the same page', function (): void {
    $user = User::factory()->create();
    seedMeal($user, 'meal-a', now()->subHour());
    seedMeal($user, 'meal-b', now()->subHours(3));

    $page = app(GetUserHealthEntriesAction::class)->handle($user, perPage: 1);

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

    $paginator = app(GetUserHealthEntriesAction::class)->handle($user);

    expect($paginator->total())->toBe(2)
        ->and($paginator->items()[0]['group_id'])->toBe('newer')
        ->and($paginator->items()[1]['group_id'])->toBe('older');
});
