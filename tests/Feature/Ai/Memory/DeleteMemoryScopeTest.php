<?php

declare(strict_types=1);

use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Ai\Exceptions\Memory\UnscopedMemoryOperationException;
use App\Ai\Facades\Memory;
use App\Models\Memory as MemoryModel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);
});

it('refuses filter-based deletes when there is no auth user and no user_id in the filter', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    MemoryModel::factory()->for($userA)->count(2)->withCategories(['junk'])->create();
    MemoryModel::factory()->for($userB)->count(3)->withCategories(['junk'])->create();

    Auth::logout();

    expect(fn (): int => Memory::delete(null, ['category' => 'junk']))
        ->toThrow(UnscopedMemoryOperationException::class);

    expect(MemoryModel::query()->count())->toBe(5);
});

it('scopes a filter-based delete to filter[user_id] when no auth user is set', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    MemoryModel::factory()->for($userA)->count(2)->withCategories(['junk'])->create();
    MemoryModel::factory()->for($userB)->count(3)->withCategories(['junk'])->create();

    Auth::logout();

    $deleted = Memory::delete(null, ['category' => 'junk', 'user_id' => $userA->id]);

    expect($deleted)->toBe(2)
        ->and(MemoryModel::query()->where('user_id', $userA->id)->count())->toBe(0)
        ->and(MemoryModel::query()->where('user_id', $userB->id)->count())->toBe(3);
});

it('ignores filter[user_id] belonging to another user when an authenticated user is set', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    MemoryModel::factory()->for($userA)->count(2)->withCategories(['junk'])->create();
    MemoryModel::factory()->for($userB)->count(3)->withCategories(['junk'])->create();

    $this->actingAs($userA);

    $deleted = Memory::delete(null, ['category' => 'junk', 'user_id' => $userB->id]);

    expect($deleted)->toBe(3)
        ->and(MemoryModel::query()->where('user_id', $userA->id)->count())->toBe(2)
        ->and(MemoryModel::query()->where('user_id', $userB->id)->count())->toBe(0);
});

it('refuses single-id deletes when there is no auth user and no scope hint', function (): void {
    $user = User::factory()->create();
    $memory = MemoryModel::factory()->for($user)->create();

    Auth::logout();

    expect(fn (): int => Memory::delete($memory->id))
        ->toThrow(UnscopedMemoryOperationException::class);

    expect(MemoryModel::query()->find($memory->id))->not->toBeNull();
});

it('rejects a single-id delete when the scoped user does not own the target memory', function (): void {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $memory = MemoryModel::factory()->for($userB)->create();

    $this->actingAs($userA);

    expect(fn (): int => Memory::delete($memory->id))
        ->toThrow(MemoryNotFoundException::class);

    expect(MemoryModel::query()->find($memory->id))->not->toBeNull();
});
