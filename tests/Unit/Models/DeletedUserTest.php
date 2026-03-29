<?php

declare(strict_types=1);

use App\Models\DeletedUser;
use Carbon\CarbonInterface;

it('has correct casts', function (): void {
    $deletedUser = new DeletedUser;
    $casts = $deletedUser->casts();

    expect($casts)
        ->toHaveKey('user_id', 'integer')
        ->toHaveKey('email', 'string')
        ->toHaveKey('deleted_at', 'datetime');
});

it('does not use timestamps', function (): void {
    $deletedUser = new DeletedUser;

    expect($deletedUser->usesTimestamps())->toBeFalse();
});

it('can be created with factory', function (): void {
    $deletedUser = DeletedUser::factory()->create();

    expect($deletedUser)
        ->toBeInstanceOf(DeletedUser::class)
        ->user_id->toBeInt()
        ->email->toBeString()
        ->deleted_at->toBeInstanceOf(CarbonInterface::class);
});

it('supports deleted days ago factory state', function (): void {
    $deletedUser = DeletedUser::factory()->deletedDaysAgo(31)->create();

    expect($deletedUser->deleted_at->diffInDays(now()))
        ->toBeGreaterThanOrEqual(30)
        ->toBeLessThanOrEqual(32);
});
