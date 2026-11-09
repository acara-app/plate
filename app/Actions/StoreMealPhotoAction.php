<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AnalysisDraft;
use App\Models\MealPhoto;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class StoreMealPhotoAction
{
    private const int MAX_SIDE = 1280;

    public function __construct(
        private ResizePhoto $resizePhoto,
        private QueueMealPhotoClassification $queueMealPhotoClassification,
    ) {}

    public function handle(User $user, string $imageBytes, string $draftToken, ?string $healthGroupId = null): MealPhoto
    {
        $jpeg = $this->resizePhoto->handle($imageBytes, self::MAX_SIDE)
            ?? throw new RuntimeException('The meal photo could not be read.');

        $path = MealPhoto::directoryFor($user).'/'.Str::uuid().'.jpg';

        throw_unless(
            Storage::disk('s3')->put($path, $jpeg),
            RuntimeException::class,
            'Could not store the meal photo.',
        );

        $photo = MealPhoto::query()->create([
            'user_id' => $user->id,
            'analysis_draft_id' => AnalysisDraft::query()->where('token_hash', AnalysisDraft::hashToken($draftToken))->value('id'),
            'health_group_id' => $healthGroupId,
            'path' => $path,
        ]);

        if ($healthGroupId !== null) {
            $this->queueMealPhotoClassification->handle($user, $healthGroupId);
        }

        return $photo;
    }
}
