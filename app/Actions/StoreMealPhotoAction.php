<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AnalysisDraft;
use App\Models\MealPhoto;
use App\Models\User;

final readonly class StoreMealPhotoAction
{
    public function __construct(
        private StoreResizedPhoto $storeResizedPhoto,
        private QueueMealPhotoClassification $queueMealPhotoClassification,
    ) {}

    public function handle(User $user, string $imageBytes, string $draftToken, ?string $healthGroupId = null): MealPhoto
    {
        $photo = MealPhoto::query()->create([
            'user_id' => $user->id,
            'analysis_draft_id' => AnalysisDraft::query()->where('token_hash', AnalysisDraft::hashToken($draftToken))->value('id'),
            'health_group_id' => $healthGroupId,
            'path' => $this->storeResizedPhoto->handle($imageBytes, MealPhoto::directoryFor($user)),
        ]);

        if ($healthGroupId !== null) {
            $this->queueMealPhotoClassification->handle($user, $healthGroupId);
        }

        return $photo;
    }
}
