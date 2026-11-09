<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\ScreenPublicMealPhotoJob;
use App\Models\MealPhotoSample;
use App\Models\PhotoAnalysisRequest;
use App\Models\User;

final readonly class KeepScannedPhoto
{
    public function __construct(
        private StoreMealPhotoAction $storeMealPhoto,
        private StoreResizedPhoto $storeResizedPhoto,
    ) {}

    public function handle(PhotoAnalysisRequest $request, ?User $user, string $imageBytes, string $draftToken): void
    {
        rescue(function () use ($request, $user, $imageBytes, $draftToken): void {
            if ($user instanceof User) {
                $this->storeMealPhoto->handle($user, $imageBytes, $draftToken);

                return;
            }

            if ($request->keep_photo) {
                $path = $this->storeResizedPhoto->handle($imageBytes, MealPhotoSample::PUBLIC_DIRECTORY);

                dispatch(new ScreenPublicMealPhotoJob($path))->afterCommit();
            }
        });
    }
}
