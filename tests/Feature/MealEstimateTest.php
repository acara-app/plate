<?php

declare(strict_types=1);

use App\Actions\DeleteHealthSampleAction;
use App\Actions\DeleteUser;
use App\Actions\LogAnalyzedMeal;
use App\Actions\LogReviewedMealAction;
use App\Actions\RecordMealCorrection;
use App\Actions\RecordMealEstimate;
use App\Actions\UpdateHealthSampleAction;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Enums\AnalysisDraftSource;
use App\Enums\EstimateValidationStatus;
use App\Models\AnalysisDraft;
use App\Models\HealthSyncSample;
use App\Models\MealEstimate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

covers(
    MealEstimate::class,
    RecordMealEstimate::class,
    RecordMealCorrection::class,
    LogReviewedMealAction::class,
    UpdateHealthSampleAction::class,
    DeleteHealthSampleAction::class,
);

/**
 * @param  array<int, array<string, mixed>>  $changes
 * @return list<array<string, mixed>>
 */
function estimatedMealItems(array $changes = []): array
{
    $items = [
        ['name' => 'Grilled chicken breast', 'portion' => '1 breast (170g)', 'grams' => 170, 'calories' => 280, 'protein' => 35, 'carbs' => 0, 'fat' => 12, 'provenance' => 'model'],
        ['name' => 'Steamed rice', 'portion' => '1 cup (160g)', 'grams' => 160, 'calories' => 210, 'protein' => 4, 'carbs' => 45, 'fat' => 0.5, 'provenance' => 'model'],
    ];

    foreach ($changes as $index => $change) {
        $items[$index] = [...$items[$index] ?? [], 'provenance' => 'user', ...$change];
    }

    return array_values($items);
}

function savedScannedMeal(User $user): HealthSyncSample
{
    $draft = AnalysisDraft::factory()->claimed($user)->create(['source' => AnalysisDraftSource::AuthenticatedSnapToTrack]);

    return resolve(LogAnalyzedMeal::class)->handle($draft, $user);
}

beforeEach(function (): void {
    Queue::fake();
    Storage::fake('s3');
    $this->user = User::factory()->create(['stripe_id' => null]);
});

it('keeps the AI estimate and the model version with a scanned meal', function (): void {
    FoodPhotoAnalyzerAgent::fake([[
        'items' => [
            ['name' => 'Buuz', 'calories' => 320, 'protein' => 18, 'carbs' => 30, 'fat' => 14, 'portion' => '4 dumplings', 'grams' => 200],
        ],
        'total_calories' => 320,
        'total_protein' => 18,
        'total_carbs' => 30,
        'total_fat' => 14,
        'confidence' => 86,
    ]]);

    $this->actingAs($this->user)->post(route('snap-to-track.analyze'), [
        'photo' => UploadedFile::fake()->image('meal.jpg', 800, 600),
    ]);

    $estimate = MealEstimate::query()->sole();

    expect($estimate->user_id)->toBe($this->user->id)
        ->and($estimate->health_group_id)->toBe(AnalysisDraft::query()->sole()->health_group_id)
        ->and($estimate->source)->toBe(AnalysisDraftSource::AuthenticatedSnapToTrack)
        ->and($estimate->model_version)->toBe(FoodPhotoAnalyzerAgent::version())
        ->and($estimate->original_prediction['items'][0]['name'])->toBe('Buuz')
        ->and($estimate->original_prediction['confidence'])->toBe(86)
        ->and($estimate->original_prediction)->not->toHaveKey('analyzer_version')
        ->and($estimate->validation_status)->toBe(EstimateValidationStatus::AiGenerated)
        ->and($estimate->corrected_value)->toBeNull();
});

it('records a correction only when foods or amounts change, and drops it when the AI values come back', function (array $edit, EstimateValidationStatus $status): void {
    $meal = savedScannedMeal($this->user);

    $this->actingAs($this->user)->put(route('health-entries.update', $meal), [
        'log_type' => 'food',
        'measured_at' => now()->toIso8601String(),
        ...$edit,
    ])->assertSessionHasNoErrors();

    $estimate = MealEstimate::query()->sole();

    expect($estimate->validation_status)->toBe($status)
        ->and($estimate->original_prediction['items'][1]['name'])->toBe('Steamed rice')
        ->and($estimate->corrected_value === null)->toBe($status === EstimateValidationStatus::AiGenerated)
        ->and($estimate->corrected_at === null)->toBe($status === EstimateValidationStatus::AiGenerated);

    $this->put(route('health-entries.update', $meal), [
        'log_type' => 'food',
        'measured_at' => now()->toIso8601String(),
        'items' => estimatedMealItems(),
    ])->assertSessionHasNoErrors();

    expect($estimate->refresh()->validation_status)->toBe(EstimateValidationStatus::AiGenerated)
        ->and($estimate->corrected_value)->toBeNull()
        ->and($estimate->corrected_at)->toBeNull();
})->with([
    'renamed food' => [['items' => estimatedMealItems([1 => ['name' => 'Fried rice']])], EstimateValidationStatus::UserCorrected],
    'smaller portion' => [['items' => estimatedMealItems([0 => ['grams' => 120, 'calories' => 198]])], EstimateValidationStatus::UserCorrected],
    'missed food added' => [['items' => estimatedMealItems([2 => ['name' => 'Kimchi', 'portion' => '1 side', 'grams' => 50, 'calories' => 12, 'protein' => 1, 'carbs' => 2, 'fat' => 0.2]])], EstimateValidationStatus::UserCorrected],
    'only the time, notes, meal type and name' => [['items' => estimatedMealItems(), 'notes' => 'Late lunch', 'meal_type' => 'lunch', 'meal_name' => 'Chicken rice'], EstimateValidationStatus::AiGenerated],
    'same food in different capitals' => [['items' => estimatedMealItems([1 => ['name' => 'STEAMED RICE']])], EstimateValidationStatus::AiGenerated],
]);

it('leaves meals saved without an AI estimate untracked when they are edited', function (): void {
    $groupId = (string) Str::uuid();

    foreach (['carbohydrates' => 45, 'protein' => 4, 'totalFat' => 0.5, 'dietaryEnergy' => 210] as $type => $value) {
        $meal = HealthSyncSample::factory()->fromWeb()->create(['user_id' => $this->user->id, 'group_id' => $groupId, 'type_identifier' => $type, 'value' => $value]);
    }

    $this->actingAs($this->user)->put(route('health-entries.update', $meal), [
        'log_type' => 'food',
        'measured_at' => now()->toIso8601String(),
        'items' => estimatedMealItems([1 => ['name' => 'Fried rice']]),
    ])->assertSessionHasNoErrors();

    expect(MealEstimate::query()->count())->toBe(0);
});

it('counts changes made while reviewing on iOS as a correction when the meal is saved', function (): void {
    $token = Str::random(64);
    AnalysisDraft::factory()->claimed($this->user)->create([
        'token_hash' => AnalysisDraft::hashToken($token),
        'source' => AnalysisDraftSource::MobileSnapToTrack,
    ]);

    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.snap-to-track.meal', ['draft' => $token]), [
        'items' => estimatedMealItems([1 => ['name' => 'Brown rice', 'calories' => 180]]),
        'measured_at' => now()->toIso8601String(),
    ])->assertCreated();

    $estimate = MealEstimate::query()->sole();

    expect($estimate->source)->toBe(AnalysisDraftSource::MobileSnapToTrack)
        ->and($estimate->validation_status)->toBe(EstimateValidationStatus::UserCorrected)
        ->and($estimate->original_prediction['items'][1]['name'])->toBe('Steamed rice')
        ->and($estimate->corrected_value['items'][1]['name'])->toBe('Brown rice')
        ->and($estimate->corrected_value['totals']['calories'])->toEqual(460)
        ->and($estimate->corrected_at)->not->toBeNull();
});

it('deletes the estimate together with its meal', function (string $removal): void {
    $meal = savedScannedMeal($this->user);

    match ($removal) {
        'meal deleted' => $this->actingAs($this->user)->delete(route('health-entries.destroy', $meal))->assertRedirect(),
        'meal edited down to one entry' => $this->actingAs($this->user)->put(route('health-entries.update', $meal), [
            'log_type' => 'food',
            'carbs_grams' => 30,
            'measured_at' => now()->toIso8601String(),
        ])->assertSessionHasNoErrors(),
        'account deleted' => resolve(DeleteUser::class)->handle($this->user),
    };

    expect(MealEstimate::query()->count())->toBe(0);
})->with(['meal deleted', 'meal edited down to one entry', 'account deleted']);

it('prunes estimates a year after the meal was saved and any left without a meal', function (): void {
    savedScannedMeal($this->user);
    savedScannedMeal($this->user);
    [$recent, $expired] = MealEstimate::query()->oldest('id')->get()->all();
    $expired->forceFill(['created_at' => now()->subMonths(MealEstimate::RETENTION_MONTHS)->subDay()])->save();
    MealEstimate::factory()->for($this->user)->create();

    $this->artisan('model:prune', ['--model' => [MealEstimate::class]])->assertSuccessful();

    expect(MealEstimate::query()->pluck('id')->all())->toBe([$recent->id]);
});
