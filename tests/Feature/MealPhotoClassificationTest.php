<?php

declare(strict_types=1);

use App\Actions\ClassifyMealPhoto;
use App\Actions\QueueMealPhotoClassification;
use App\Actions\RecordConsent;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Ai\Classifiers\MealPhotoClassifier;
use App\Enums\ConsentPurpose;
use App\Enums\ConsentSource;
use App\Jobs\ClassifyMealPhotoJob;
use App\Listeners\TrackClassificationUsage;
use App\Models\AiUsage;
use App\Models\MealPhoto;
use App\Models\MealPhotoClassification;
use App\Models\MealPhotoSample;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Classification;

covers(
    MealPhotoClassifier::class,
    ClassifyMealPhoto::class,
    QueueMealPhotoClassification::class,
    ClassifyMealPhotoJob::class,
    TrackClassificationUsage::class,
    MealPhotoClassification::class,
    MealPhotoSample::class,
    RecordConsent::class,
);

beforeEach(function (): void {
    Storage::fake('s3');
    Classification::fake();
    FoodPhotoAnalyzerAgent::fake([[
        'items' => [['name' => 'Salad', 'calories' => 180, 'protein' => 3, 'carbs' => 14, 'fat' => 13, 'portion' => '1 bowl']],
        'total_calories' => 180,
        'total_protein' => 3,
        'total_carbs' => 14,
        'total_fat' => 13,
        'confidence' => 88,
    ]]);

    $this->user = User::factory()->create();
});

it('checks the photo of a meal saved by someone who opted in', function (): void {
    resolve(RecordConsent::class)->handle($this->user, ConsentPurpose::ImproveFoodRecognition, true, ConsentSource::Settings);

    $this->actingAs($this->user)->post(route('snap-to-track.analyze'), ['photo' => UploadedFile::fake()->image('meal.jpg')]);

    $classification = MealPhotoClassification::query()->sole();

    expect($classification->meal_photo_id)->toBe(MealPhoto::query()->sole()->id)
        ->and($classification->answers)->toHaveKeys(['food_type', 'cuisine', 'dish_type'])
        ->and(AiUsage::query()->where('agent', MealPhotoClassifier::class)->sole()->user_id)->toBeNull();
});

it('never sends the photos of people who have not opted in', function (): void {
    $this->actingAs($this->user)->post(route('snap-to-track.analyze'), ['photo' => UploadedFile::fake()->image('meal.jpg')]);

    expect(MealPhoto::query()->count())->toBe(1);
    Classification::assertNothingClassified();
});

it('deletes the photo checks and copies and sends nothing more after someone opts out', function (): void {
    resolve(RecordConsent::class)->handle($this->user, ConsentPurpose::ImproveFoodRecognition, true, ConsentSource::Settings);
    $photo = MealPhoto::factory()->for($this->user)->create();
    MealPhotoClassification::factory()->for($photo)->create();
    $copy = MealPhotoSample::factory()->for($this->user)->create();
    Storage::disk('s3')->put($copy->path, 'jpeg-bytes');

    $this->actingAs($this->user)->patch(route('user-privacy.update'), ['improve_food_recognition' => '0']);
    dispatch_sync(new ClassifyMealPhotoJob($photo));

    expect(MealPhotoClassification::query()->count())->toBe(0)
        ->and(MealPhotoSample::query()->count())->toBe(0);
    Storage::disk('s3')->assertMissing($copy->path);
    Classification::assertNothingClassified();
});
