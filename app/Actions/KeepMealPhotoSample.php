<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\MealPhoto;
use App\Models\MealPhotoClassification;
use App\Models\MealPhotoSample;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final readonly class KeepMealPhotoSample
{
    public function handle(MealPhoto $photo, MealPhotoClassification $classification): ?MealPhotoSample
    {
        if (! $classification->passesScreening()) {
            return null;
        }

        $path = MealPhotoSample::directoryFor($photo->user).'/'.basename($photo->path);

        throw_unless(
            Storage::disk('s3')->copy($photo->path, $path),
            RuntimeException::class,
            'Could not copy the meal photo.',
        );

        return MealPhotoSample::query()->updateOrCreate(['meal_photo_id' => $photo->id], [
            'user_id' => $photo->user_id,
            'path' => $path,
            'version' => $classification->version,
            'answers' => $classification->answers,
        ]);
    }
}
