<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\HealthEntry\DestroyHealthEntryController;
use App\Jobs\AggregateUserDayJob;
use App\Models\HealthSyncSample;
use App\Models\MealPhoto;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

covers(DestroyHealthEntryController::class);

it('requires a bearer token', function (): void {
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create();

    $this->deleteJson(route('api.v2.health-entries.destroy', $sample))->assertUnauthorized();
});

it('deletes a whole meal with its photo and returns the local day it was on', function (): void {
    Queue::fake();
    Storage::fake(MealPhoto::DISK);
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
    $groupId = (string) Str::uuid();
    $sample = HealthSyncSample::factory()->carbohydrates()->create(['user_id' => $user->id, 'group_id' => $groupId, 'entry_source' => 'mobile', 'measured_at' => '2026-10-17 23:30:00']);
    HealthSyncSample::factory()->create(['user_id' => $user->id, 'group_id' => $groupId, 'type_identifier' => 'dietaryEnergy', 'value' => 300, 'unit' => 'kcal', 'entry_source' => 'mobile', 'measured_at' => '2026-10-17 23:30:00']);
    $photo = MealPhoto::factory()->create(['user_id' => $user->id, 'health_group_id' => $groupId]);
    Storage::disk(MealPhoto::DISK)->put($photo->path, 'jpeg-bytes');
    Sanctum::actingAs($user, ['chat:converse']);

    $this->deleteJson(route('api.v2.health-entries.destroy', $sample))
        ->assertOk()
        ->assertExactJson(['date' => '2026-10-18']);

    expect(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(MealPhoto::query()->count())->toBe(0);
    Storage::disk(MealPhoto::DISK)->assertMissing($photo->path);
    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => $job->uniqueId() === $user->id.':2026-10-17');
});

it('cannot delete another user’s entry', function (): void {
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create();
    Sanctum::actingAs(User::factory()->create(), ['chat:converse']);

    $this->deleteJson(route('api.v2.health-entries.destroy', $sample))->assertForbidden();

    expect($sample->fresh())->not->toBeNull();
});

it('does not delete samples that are not logbook entries', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->fromWeb()->create(['user_id' => $user->id, 'type_identifier' => 'water', 'value' => 500, 'unit' => 'mL']);
    Sanctum::actingAs($user, ['chat:converse']);

    $this->deleteJson(route('api.v2.health-entries.destroy', $sample))->assertNotFound();

    expect($sample->fresh())->not->toBeNull();
});
