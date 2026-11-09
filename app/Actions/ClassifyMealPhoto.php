<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Classifiers\MealPhotoClassifier;
use App\Enums\ConsentPurpose;
use App\Models\MealPhoto;
use App\Models\MealPhotoClassification;

final readonly class ClassifyMealPhoto
{
    public function __construct(private MealPhotoClassifier $classifier) {}

    public function handle(MealPhoto $photo): ?MealPhotoClassification
    {
        if (! $photo->user->hasConsentedTo(ConsentPurpose::ImproveFoodRecognition)) {
            return null;
        }

        $response = $this->classifier->classify($photo->path);

        return MealPhotoClassification::query()->updateOrCreate(['meal_photo_id' => $photo->id], [
            'version' => MealPhotoClassifier::VERSION,
            'provider' => $response->meta->provider ?? 'unknown',
            'model' => $response->meta->model ?? 'unknown',
            'answers' => MealPhotoClassifier::answers($response),
        ]);
    }
}
