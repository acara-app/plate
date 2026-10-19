<?php

declare(strict_types=1);

use App\Actions\DeleteHealthSampleAction;
use App\Actions\DeleteUser;
use App\Actions\LogReviewedMealAction;
use App\Actions\StoreMealPhotoAction;
use App\Actions\UpdateHealthSampleAction;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\TierEntitlement;
use App\Enums\SubscriptionTier;
use App\Http\Controllers\HealthEntry\ShowMealPhotoController;
use App\Http\Controllers\SnapToTrack\AnalyzeSnapToTrackPhotoController;
use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\MealPhoto;
use App\Models\User;
use App\Services\HealthEntryAssembler;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

covers(
    StoreMealPhotoAction::class,
    MealPhoto::class,
    ShowMealPhotoController::class,
    AnalyzeSnapToTrackPhotoController::class,
    LogReviewedMealAction::class,
    DeleteHealthSampleAction::class,
    UpdateHealthSampleAction::class,
    DeleteUser::class,
    HealthEntryAssembler::class,
);

function fakeMealPhotoAnalysis(): void
{
    FoodPhotoAnalyzerAgent::fake([[
        'items' => [
            ['name' => 'Salad', 'calories' => 180, 'protein' => 3, 'carbs' => 14, 'fat' => 13, 'portion' => '1 bowl'],
        ],
        'total_calories' => 180,
        'total_protein' => 3,
        'total_carbs' => 14,
        'total_fat' => 13,
        'confidence' => 88,
    ]]);
}

function storedMealPhoto(User $user, ?string $groupId = null, ?string $createdAt = null): MealPhoto
{
    $photo = MealPhoto::factory()->create([
        'user_id' => $user->id,
        'health_group_id' => $groupId,
        'path' => MealPhoto::directoryFor($user).'/'.Str::uuid().'.jpg',
    ]);

    if ($createdAt !== null) {
        $photo->forceFill(['created_at' => $createdAt])->save();
    }

    Storage::disk(MealPhoto::DISK)->put($photo->path, 'jpeg-bytes');

    return $photo;
}

beforeEach(function (): void {
    Storage::fake(MealPhoto::DISK);
    $this->user = User::factory()->create(['stripe_id' => null]);
    RateLimiter::clear('snap-to-track-analyze:'.$this->user->id);
});

it('keeps a signed-in scan photo privately with the saved meal and only its owner can see it', function (): void {
    fakeMealPhotoAnalysis();

    $response = $this->actingAs($this->user)->post(route('snap-to-track.analyze'), [
        'photo' => UploadedFile::fake()->image('meal.jpg', 2000, 1500),
    ]);

    $photo = MealPhoto::query()->sole();
    $draft = AnalysisDraft::query()->sole();
    $stored = (string) Storage::disk(MealPhoto::DISK)->get($photo->path);
    $size = getimagesizefromstring($stored);

    expect($photo->user_id)->toBe($this->user->id)
        ->and($photo->analysis_draft_id)->toBe($draft->id)
        ->and($photo->health_group_id)->toBeNull()
        ->and($photo->path)->toStartWith('meal-photos/'.$this->user->id.'/')
        ->and([$size[0], $size[1], $size['mime']])->toBe([1280, 960, 'image/jpeg'])
        ->and(str_contains($stored, "Exif\0\0"))->toBeFalse();

    $token = Str::after((string) $response->headers->get('Location'), '/review/');
    $this->post(route('snap-to-track.review.store', ['draft' => $token]), [
        'items' => [['name' => 'Salad', 'portion' => '1 bowl', 'calories' => 180, 'protein' => 3, 'carbs' => 14, 'fat' => 13, 'provenance' => 'model']],
        'measured_at' => now()->toIso8601String(),
    ])->assertRedirectToRoute('health-entries.index', ['date' => now()->toDateString()]);

    $groupId = (string) $draft->refresh()->health_group_id;
    expect($photo->refresh()->health_group_id)->toBe($groupId);

    $photoUrl = route('health-entries.photo', ['group' => $groupId], absolute: false);
    $this->get(route('health-entries.index'))->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->where('entries.0.photo_url', $photoUrl));

    $this->get($photoUrl)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $this->actingAs(User::factory()->create())->get($photoUrl)->assertNotFound();

    $this->actingAs($this->user)
        ->delete(route('health-entries.destroy', HealthSyncSample::query()->where('group_id', $groupId)->firstOrFail()))
        ->assertRedirect();

    expect(MealPhoto::query()->count())->toBe(0);
    Storage::disk(MealPhoto::DISK)->assertMissing($photo->path);
});

it('keeps no photo when the scan is blocked by the credit limit', function (): void {
    app()->instance(ResolvesUserTier::class, new readonly class implements ResolvesUserTier
    {
        public function resolve(User $user): TierEntitlement
        {
            return new TierEntitlement(tier: SubscriptionTier::Free);
        }
    });
    config()->set('plate.tier_limits.free', [
        'rolling' => ['limit' => 0.0, 'period_hours' => 24],
        'weekly' => ['limit' => 0.0, 'period_days' => 7],
    ]);

    $this->actingAs($this->user)->post(route('snap-to-track.analyze'), [
        'photo' => UploadedFile::fake()->image('meal.jpg'),
    ])->assertRedirectToRoute('snap-to-track.index');

    expect(MealPhoto::query()->count())->toBe(0)
        ->and(Storage::disk(MealPhoto::DISK)->allFiles())->toBe([]);
});

it('stores other image formats as a plain jpeg and rejects unreadable data', function (): void {
    $token = Str::random(64);
    AnalysisDraft::factory()->claimed($this->user)->create(['token_hash' => AnalysisDraft::hashToken($token)]);
    $action = resolve(StoreMealPhotoAction::class);

    $photo = $action->handle($this->user, (string) UploadedFile::fake()->image('meal.png', 300, 200)->get(), $token);

    expect(getimagesizefromstring((string) Storage::disk(MealPhoto::DISK)->get($photo->path))['mime'])->toBe('image/jpeg')
        ->and(fn () => $action->handle($this->user, 'not an image', $token))->toThrow(RuntimeException::class);
});

it('removes the photo when an edit turns the meal into a single entry', function (): void {
    $groupId = (string) Str::uuid();
    $sample = HealthSyncSample::factory()->carbohydrates()->fromWeb()->create(['user_id' => $this->user->id, 'group_id' => $groupId]);
    HealthSyncSample::factory()->fromWeb()->create(['user_id' => $this->user->id, 'group_id' => $groupId, 'type_identifier' => 'dietaryEnergy', 'value' => 180, 'unit' => 'kcal']);
    $photo = storedMealPhoto($this->user, $groupId);

    $this->actingAs($this->user)->put(route('health-entries.update', $sample), [
        'log_type' => 'food',
        'carbs_grams' => 30,
        'measured_at' => now()->toIso8601String(),
    ])->assertSessionHasNoErrors();

    expect(MealPhoto::query()->count())->toBe(0);
    Storage::disk(MealPhoto::DISK)->assertMissing($photo->path);
});

it('deletes every meal photo with the account', function (): void {
    $photo = storedMealPhoto($this->user, (string) Str::uuid());

    resolve(DeleteUser::class)->handle($this->user);

    Storage::disk(MealPhoto::DISK)->assertMissing($photo->path);
    expect(MealPhoto::query()->count())->toBe(0);
});

it('prunes photos of meals that were never saved after a day', function (): void {
    $abandoned = storedMealPhoto($this->user, null, now()->subDays(2)->toDateTimeString());
    $recent = storedMealPhoto($this->user);
    $saved = storedMealPhoto($this->user, (string) Str::uuid(), now()->subDays(2)->toDateTimeString());

    $this->artisan('model:prune', ['--model' => [MealPhoto::class]])->assertSuccessful();

    Storage::disk(MealPhoto::DISK)->assertMissing($abandoned->path);
    Storage::disk(MealPhoto::DISK)->assertExists([$recent->path, $saved->path]);
    expect(MealPhoto::query()->pluck('id')->all())->toEqualCanonicalizing([$recent->id, $saved->id]);
});
