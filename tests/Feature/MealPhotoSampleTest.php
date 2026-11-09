<?php

declare(strict_types=1);

use App\Actions\KeepMealPhotoSample;
use App\Actions\RecordConsent;
use App\Enums\ConsentPurpose;
use App\Enums\ConsentSource;
use App\Jobs\ClassifyMealPhotoJob;
use App\Models\MealPhoto;
use App\Models\MealPhotoSample;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Classification;
use Laravel\Ai\Responses\Data\BooleanAnswer;
use Laravel\Ai\Responses\Data\ChoiceAnswer;
use Laravel\Ai\Responses\Data\ScoreAnswer;

covers(KeepMealPhotoSample::class, MealPhotoSample::class, ClassifyMealPhotoJob::class);

beforeEach(function (): void {
    Storage::fake('s3');
});

it('keeps a copy of a photo that passes the check, even after the meal is deleted', function (float $face, int $copies): void {
    $user = User::factory()->create();
    resolve(RecordConsent::class)->handle($user, ConsentPurpose::ImproveFoodRecognition, true, ConsentSource::Settings);
    $photo = MealPhoto::factory()->for($user)->create(['health_group_id' => (string) Str::uuid()]);
    Storage::disk('s3')->put($photo->path, 'jpeg-bytes');
    Classification::fake([[
        'subject' => new ChoiceAnswer('food', ['food' => 0.97, 'other' => 0.03]),
        'face' => new BooleanAnswer($face),
        'personal_details' => new BooleanAnswer(0.01),
        'image_quality' => new ScoreAnswer(2.8, [0.0, 0.0, 0.2, 0.8], []),
    ]]);

    dispatch_sync(new ClassifyMealPhotoJob($photo));
    $photo->deleteWithFile();

    expect(MealPhotoSample::query()->count())->toBe($copies)
        ->and(Storage::disk('s3')->allFiles(MealPhotoSample::DIRECTORY))->toHaveCount($copies);
})->with(['a clear meal' => [0.01, 1], 'a face' => [0.9, 0]]);

it('deletes copies after 24 months', function (): void {
    $old = MealPhotoSample::factory()->create(['created_at' => now()->subMonths(MealPhotoSample::RETENTION_MONTHS)->subDay()]);
    $recent = MealPhotoSample::factory()->create();
    Storage::disk('s3')->put($old->path, 'jpeg-bytes');

    $this->artisan('model:prune', ['--model' => [MealPhotoSample::class]])->assertSuccessful();

    expect(MealPhotoSample::query()->pluck('id')->all())->toBe([$recent->id]);
    Storage::disk('s3')->assertMissing($old->path);
});
