<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsentPurpose;
use App\Jobs\ClassifyMealPhotoJob;
use App\Models\MealPhoto;
use App\Models\User;

final readonly class QueueMealPhotoClassification
{
    public function handle(User $user, string $groupId): void
    {
        if (! $user->hasConsentedTo(ConsentPurpose::ImproveFoodRecognition)) {
            return;
        }

        $photos = MealPhoto::query()
            ->where('user_id', $user->id)
            ->where('health_group_id', $groupId)
            ->get();

        foreach ($photos as $photo) {
            dispatch(new ClassifyMealPhotoJob($photo))->afterCommit();
        }
    }
}
